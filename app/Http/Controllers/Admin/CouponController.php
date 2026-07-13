<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\User;
use App\Models\UserVoucher;
use App\Support\LocalDateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::query()
            ->withCount(['usages', 'userVouchers'])
            ->latest()
            ->take(50)
            ->get();

        return view('admin.coupons', [
            'coupons' => $coupons,
            'activeCoupons' => $coupons->where('is_active', true)->count(),
            'usedCount' => $coupons->sum('used_count'),
            'personalVoucherCount' => UserVoucher::query()->count(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateCoupon($request);

        Coupon::query()->create($this->couponPayload($validated, $request));

        return redirect()->route('admin.coupons')->with('success', 'Đã tạo voucher mới.');
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $this->validateCoupon($request, $coupon);

        $coupon->forceFill($this->couponPayload($validated, $request))->save();

        return redirect()->route('admin.coupons')->with('success', 'Đã cập nhật voucher ' . $coupon->code . '.');
    }

    public function toggle(Coupon $coupon)
    {
        $coupon->forceFill([
            'is_active' => !$coupon->is_active,
        ])->save();

        return redirect()->route('admin.coupons')->with('success', 'Đã đổi trạng thái voucher ' . $coupon->code . '.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return redirect()->route('admin.coupons')->with('success', 'Đã xóa mềm voucher ' . $coupon->code . '.');
    }

    public function assign(Request $request)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'coupon_id' => ['required', 'integer', 'exists:coupons,id'],
            'email' => ['required', 'email', 'max:120', 'exists:users,email'],
            'expires_at' => ['nullable', 'date'],
        ], [
            'coupon_id.required' => 'Vui lòng chọn voucher.',
            'email.exists' => 'Không tìm thấy khách hàng với email này.',
        ]);

        $user = User::query()
            ->where('email', Str::lower(trim($validated['email'])))
            ->firstOrFail();

        UserVoucher::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'coupon_id' => (int) $validated['coupon_id'],
            ],
            [
                'assigned_at' => now(),
                'used_at' => null,
                'expires_at' => $validated['expires_at'] ?? null,
            ]
        );

        return redirect()->route('admin.coupons')->with('success', 'Đã gán voucher cá nhân cho ' . $user->email . '.');
    }

    private function validateCoupon(Request $request, ?Coupon $coupon = null): array
    {
        $request->merge([
            'code' => Str::upper(trim((string) $request->input('code'))),
        ]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:2', 'max:160'],
            'code' => [
                'required',
                'string',
                'max:80',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('coupons', 'code')->ignore(optional($coupon)->id),
            ],
            'type' => ['required', Rule::in([Coupon::TYPE_PERCENT, Coupon::TYPE_FIXED, Coupon::TYPE_GIFT])],
            'value' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'min_order_total' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'max_discount' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'title.required' => 'Vui lòng nhập tiêu đề voucher.',
            'code.required' => 'Vui lòng nhập mã voucher.',
            'code.regex' => 'Mã voucher chỉ nên gồm chữ in hoa, số, dấu gạch dưới hoặc gạch ngang.',
            'code.unique' => 'Mã voucher này đã tồn tại.',
            'ends_at.after_or_equal' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
        ]);

        $value = (int) ($validated['value'] ?? 0);
        if ($validated['type'] !== Coupon::TYPE_GIFT && $value <= 0) {
            throw ValidationException::withMessages([
                'value' => 'Voucher giảm giá cần có giá trị lớn hơn 0.',
            ]);
        }

        if ($validated['type'] === Coupon::TYPE_PERCENT && $value > 100) {
            throw ValidationException::withMessages([
                'value' => 'Voucher phần trăm không được vượt quá 100%.',
            ]);
        }

        return $validated;
    }

    private function couponPayload(array $validated, Request $request): array
    {
        $isGift = $validated['type'] === Coupon::TYPE_GIFT;

        return [
            'title' => trim($validated['title']),
            'code' => $validated['code'],
            'type' => $validated['type'],
            'value' => $isGift ? 0 : (int) ($validated['value'] ?? 0),
            'min_order_total' => $validated['min_order_total'] ?? 0,
            'max_discount' => $isGift ? null : ($validated['max_discount'] ?? null),
            'usage_limit' => $validated['usage_limit'] ?? null,
            'per_customer_limit' => $validated['per_customer_limit'] ?? null,
            'starts_at' => LocalDateTime::fromLocalInput($validated['starts_at'] ?? null),
            'ends_at' => LocalDateTime::fromLocalInput($validated['ends_at'] ?? null),
            'is_active' => $request->boolean('is_active'),
            'is_public' => $request->boolean('is_public'),
            'description' => $validated['description'] ?? null,
        ];
    }
}
