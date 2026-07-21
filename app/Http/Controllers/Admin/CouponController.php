<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignCouponRequest;
use App\Http\Requests\Admin\UpsertCouponRequest;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\UserVoucher;
use App\Services\AdminCouponService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CouponController extends Controller
{
    private const STATUSES = [
        'active',
        'scheduled',
        'expired',
        'exhausted',
        'unavailable',
        'inactive',
        'archived',
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'type' => ['nullable', Rule::in([Coupon::TYPE_PERCENT, Coupon::TYPE_FIXED, Coupon::TYPE_GIFT])],
            'scope' => ['nullable', Rule::in(['public', 'private'])],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50])],
        ]);

        $query = Coupon::query()->with('giftProduct')->withCount(['usages', 'userVouchers']);
        $status = $filters['status'] ?? null;

        if ($status === 'archived') {
            $query->onlyTrashed();
        } else {
            $this->applyStatusFilter($query, $status);
        }

        $keyword = trim((string) ($filters['q'] ?? ''));
        if ($keyword !== '') {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $keyword).'%';
            $query->where(function (Builder $inner) use ($like) {
                $inner->where('code', 'like', $like)
                    ->orWhere('title', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (($filters['scope'] ?? null) === 'public') {
            $query->where('is_public', true);
        } elseif (($filters['scope'] ?? null) === 'private') {
            $query->where('is_public', false);
        }

        $coupons = $query
            ->latest('updated_at')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->appends($request->query());

        return view('admin.coupons', [
            'coupons' => $coupons,
            'stats' => $this->stats(),
        ]);
    }

    public function create(): View
    {
        return view('admin.coupons.create', [
            'coupon' => new Coupon([
                'type' => Coupon::TYPE_PERCENT,
                'value' => 10,
                'min_order_total' => 0,
                'gift_quantity' => 1,
                'is_active' => false,
                'is_public' => true,
                'per_customer_limit' => 1,
            ]),
            'products' => $this->giftProducts(),
        ]);
    }

    public function store(UpsertCouponRequest $request, AdminCouponService $coupons): RedirectResponse
    {
        $coupon = $coupons->create($request->validated(), $this->auditContext($request));

        return redirect()
            ->route('admin.coupons.show', $coupon)
            ->with('success', 'Đã tạo voucher '.$coupon->code.'.');
    }

    public function show(Coupon $coupon): View
    {
        $coupon->load(['giftProduct'])->loadCount(['usages', 'userVouchers']);
        $usages = $coupon->usages()
            ->with(['order', 'user'])
            ->latest('used_at')
            ->paginate(15, ['*'], 'usage_page')
            ->appends(request()->query());
        $assignments = $coupon->userVouchers()
            ->with(['user', 'coupon'])
            ->latest('assigned_at')
            ->paginate(15, ['*'], 'assignment_page')
            ->appends(request()->query());

        return view('admin.coupons.show', compact('coupon', 'usages', 'assignments'));
    }

    public function edit(Coupon $coupon): View
    {
        $coupon->load('giftProduct')->loadCount('usages');

        return view('admin.coupons.edit', [
            'coupon' => $coupon,
            'products' => $this->giftProducts(),
        ]);
    }

    public function update(
        UpsertCouponRequest $request,
        Coupon $coupon,
        AdminCouponService $coupons
    ): RedirectResponse {
        $coupon = $coupons->update($coupon, $request->validated(), $this->auditContext($request));

        return redirect()
            ->route('admin.coupons.edit', $coupon)
            ->with('success', 'Đã cập nhật voucher '.$coupon->code.'.');
    }

    public function toggle(Request $request, Coupon $coupon, AdminCouponService $coupons): RedirectResponse
    {
        $coupon = $coupons->toggle($coupon, $this->auditContext($request));

        return back()->with('success', $coupon->is_active ? 'Voucher đã được bật.' : 'Voucher đã được tạm ngưng.');
    }

    public function destroy(Request $request, Coupon $coupon, AdminCouponService $coupons): RedirectResponse
    {
        $code = $coupon->code;
        $coupons->archive($coupon, $this->auditContext($request));

        return redirect()->route('admin.coupons')->with('success', 'Đã lưu trữ voucher '.$code.'.');
    }

    public function restore(Request $request, int $coupon, AdminCouponService $coupons): RedirectResponse
    {
        $coupon = $coupons->restore($coupon, $this->auditContext($request));

        return redirect()
            ->route('admin.coupons.edit', $coupon)
            ->with('success', 'Đã khôi phục voucher ở trạng thái tạm ngưng để kiểm tra lại.');
    }

    public function assign(AssignCouponRequest $request, AdminCouponService $coupons): RedirectResponse
    {
        $result = $coupons->assign($request->validated(), $this->auditContext($request));
        $message = 'Đã phát mới '.number_format($result['created']).' voucher';

        if ($result['updated'] > 0) {
            $message .= ', cập nhật hạn '.number_format($result['updated']).' voucher';
        }

        if ($result['skipped'] > 0) {
            $message .= ', bỏ qua '.number_format($result['skipped']).' tài khoản đã nhận hoặc đã dùng';
        }

        return redirect()
            ->route('admin.coupons.show', $result['coupon'])
            ->with('success', $message.'.');
    }

    private function applyStatusFilter(Builder $query, ?string $status): void
    {
        if (! $status) {
            return;
        }

        if ($status === 'inactive') {
            $query->where('is_active', false);

            return;
        }

        if ($status === 'scheduled') {
            $query->where('is_active', true)->where('starts_at', '>', now());

            return;
        }

        if ($status === 'expired') {
            $query->where('ends_at', '<', now());

            return;
        }

        if ($status === 'exhausted') {
            $query->whereNotNull('usage_limit')->whereColumn('used_count', '>=', 'usage_limit');

            return;
        }

        if ($status === 'unavailable') {
            $query->where('type', Coupon::TYPE_GIFT)
                ->whereNotNull('gift_product_id')
                ->whereDoesntHave('giftProduct', function (Builder $product) {
                    $product->where('is_active', true)
                        ->whereNull('products.deleted_at')
                        ->whereColumn('products.stock', '>=', 'coupons.gift_quantity');
                });

            return;
        }

        if ($status === 'active') {
            $query->where('is_active', true)
                ->where(function (Builder $inner) {
                    $inner->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->where(function (Builder $inner) {
                    $inner->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                })
                ->where(function (Builder $inner) {
                    $inner->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
                })
                ->where(function (Builder $inner) {
                    $inner->where('type', '!=', Coupon::TYPE_GIFT)
                        ->orWhereNull('gift_product_id')
                        ->orWhereHas('giftProduct', function (Builder $product) {
                            $product->where('is_active', true)
                                ->whereNull('products.deleted_at')
                                ->whereColumn('products.stock', '>=', 'coupons.gift_quantity');
                        });
                });
        }
    }

    private function stats(): array
    {
        $active = Coupon::query();
        $this->applyStatusFilter($active, 'active');

        return [
            'total' => Coupon::query()->count(),
            'active' => $active->count(),
            'used' => (int) Coupon::query()->sum('used_count'),
            'assigned' => UserVoucher::query()->count(),
            'archived' => Coupon::onlyTrashed()->count(),
        ];
    }

    private function giftProducts()
    {
        return Product::query()
            ->with('category')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'category_id', 'name', 'sku', 'unit', 'stock', 'price', 'sale_price', 'is_active']);
    }

    private function auditContext(Request $request): array
    {
        return [
            'user_id' => optional($request->user())->id,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
        ];
    }
}
