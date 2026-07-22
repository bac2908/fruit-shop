<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignCustomerVoucherRequest;
use App\Http\Requests\Admin\SuspendCustomerRequest;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use App\Services\AdminCouponService;
use App\Services\AdminCustomerService;
use App\Services\SecurityAuditService;
use App\Support\LocalDateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CustomerController extends Controller
{
    private const SEGMENTS = ['new', 'repeat', 'vip', 'churn_risk'];

    private const STATUSES = ['active', 'suspended', 'locked', 'unverified'];

    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $query = $this->customerListQuery($filters);
        $customers = $this->applySort($query, $filters['sort'] ?? 'newest')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->appends($request->query());

        $customers->getCollection()->each(function (User $customer) {
            $customer->setAttribute('segment_label', $this->segmentLabel($customer));
        });

        return view('admin.customers', [
            'customers' => $customers,
            'customerSummary' => $this->summary(),
            'accountStatusLabels' => User::accountStatusLabels(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $customers = $this->applySort($this->customerListQuery($filters), $filters['sort'] ?? 'newest')
            ->limit(5000)
            ->get();
        $fileName = 'khach-hang-'.LocalDateTime::now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($customers) {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['ID', 'Họ tên', 'Email', 'Số điện thoại', 'Trạng thái', 'Xác minh email', 'Số đơn', 'Đơn hoàn tất', 'Tổng chi tiêu', 'Đơn gần nhất', 'Ngày đăng ký']);

            foreach ($customers as $customer) {
                fputcsv($output, [
                    $customer->id,
                    $customer->name,
                    $customer->email,
                    $customer->phone,
                    User::accountStatusLabels()[$customer->account_status] ?? $customer->account_status,
                    $customer->hasVerifiedEmail() ? 'Đã xác minh' : 'Chưa xác minh',
                    (int) $customer->orders_count,
                    (int) $customer->completed_orders_count,
                    (int) ($customer->lifetime_value ?? 0),
                    LocalDateTime::format($customer->last_order_at),
                    LocalDateTime::format($customer->created_at),
                ]);
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function show(User $customer, AdminCustomerService $customers): View
    {
        $customer = $this->customer($customer);
        $customer->load(['addresses' => fn ($query) => $query->orderByDesc('is_default')->latest(), 'suspendedBy']);

        $orders = $customer->orders()
            ->with(['items.product.images'])
            ->latest()
            ->paginate(10, ['*'], 'order_page')
            ->appends(request()->query());
        $vouchers = $customer->vouchers()
            ->with('coupon')
            ->latest('assigned_at')
            ->paginate(10, ['*'], 'voucher_page')
            ->appends(request()->query());
        $orderMetrics = $this->orderMetrics($customer);

        return view('admin.customers.show', [
            'customer' => $customer,
            'orders' => $orders,
            'vouchers' => $vouchers,
            'metrics' => $orderMetrics,
            'membership' => $customers->membership($orderMetrics['total_spent']),
            'assignableCoupons' => Coupon::query()->valid()->orderBy('ends_at')->orderBy('title')->take(75)->get(),
            'securityEvents' => $this->securityEvents($customer),
            'failedLoginAttempts' => $this->failedLoginAttempts($customer),
        ]);
    }

    public function update(
        UpdateCustomerRequest $request,
        User $customer,
        AdminCustomerService $customers
    ): RedirectResponse {
        $customer = $this->customer($customer);
        $customers->updateProfile($customer, $request->validated(), $this->auditContext($request));

        return back()->with('success', 'Đã cập nhật hồ sơ và ghi chú nội bộ của khách hàng.');
    }

    public function suspend(
        SuspendCustomerRequest $request,
        User $customer,
        AdminCustomerService $customers
    ): RedirectResponse {
        $customer = $this->customer($customer);
        $customers->suspend($customer, $request->validated('reason'), $this->auditContext($request));

        return back()->with('success', 'Đã tạm ngưng tài khoản và đăng xuất khách hàng khỏi mọi thiết bị.');
    }

    public function activate(Request $request, User $customer, AdminCustomerService $customers): RedirectResponse
    {
        $customer = $this->customer($customer);
        $customers->activate($customer, $this->auditContext($request));

        return back()->with('success', 'Đã kích hoạt lại tài khoản khách hàng.');
    }

    public function unlock(Request $request, User $customer, AdminCustomerService $customers): RedirectResponse
    {
        $customer = $this->customer($customer);
        $customers->unlock($customer, $this->auditContext($request));

        return back()->with('success', 'Đã xóa giới hạn đăng nhập tạm thời cho khách hàng.');
    }

    public function revokeSessions(Request $request, User $customer, AdminCustomerService $customers): RedirectResponse
    {
        $customer = $this->customer($customer);
        $customers->revokeSessions($customer, $this->auditContext($request));

        return back()->with('success', 'Đã đăng xuất tài khoản khỏi mọi thiết bị.');
    }

    public function resendVerification(
        Request $request,
        User $customer,
        SecurityAuditService $audit
    ): RedirectResponse {
        $customer = $this->customer($customer);

        if ($customer->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Email của khách hàng đã được xác minh.',
            ]);
        }

        try {
            $customer->sendEmailVerificationNotification();
            $audit->record('admin_customer_verification_resent', $this->auditContext($request), [
                'customer_id' => $customer->id,
            ]);
        } catch (Throwable $exception) {
            Log::error('Admin could not resend customer verification email.', [
                'customer_id' => $customer->id,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            return back()->with('error', 'Chưa gửi được email xác minh. Vui lòng kiểm tra cấu hình mail.');
        }

        return back()->with('success', 'Đã gửi lại email xác minh cho khách hàng.');
    }

    public function assignVoucher(
        AssignCustomerVoucherRequest $request,
        User $customer,
        AdminCouponService $coupons
    ): RedirectResponse {
        $customer = $this->customer($customer);
        $result = $coupons->assign([
            'coupon_id' => (int) $request->validated('coupon_id'),
            'target' => 'single',
            'email' => $customer->email,
            'expires_at' => $request->validated('expires_at'),
        ], $this->auditContext($request));

        $message = $result['created'] > 0
            ? 'Đã phát voucher cho khách hàng và tạo thông báo trong tài khoản.'
            : ($result['updated'] > 0
                ? 'Đã cập nhật hạn sử dụng riêng của voucher.'
                : 'Khách hàng đã nhận hoặc đã sử dụng voucher này.');

        return back()->with('success', $message);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'segment' => ['nullable', Rule::in(self::SEGMENTS)],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'spend_desc', 'orders_desc', 'last_order'])],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50])],
        ]);
    }

    private function customerListQuery(array $filters): Builder
    {
        $query = $this->baseCustomerQuery();
        $keyword = trim((string) ($filters['q'] ?? ''));

        if ($keyword !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $keyword).'%';
            $query->where(function (Builder $inner) use ($like) {
                $inner->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        $this->applyStatusFilter($query, $filters['status'] ?? null);
        $this->applySegmentFilter($query, $filters['segment'] ?? null);

        return $this->withCustomerMetrics($query);
    }

    private function baseCustomerQuery(): Builder
    {
        return User::query()->where('role', 'customer');
    }

    private function withCustomerMetrics(Builder $query): Builder
    {
        return $query
            ->withCount([
                'orders',
                'orders as completed_orders_count' => fn (Builder $orders) => $orders->where('status', Order::STATUS_DONE),
                'addresses',
                'vouchers as vouchers_count',
            ])
            ->withSum([
                'orders as lifetime_value' => fn (Builder $orders) => $orders->where('status', Order::STATUS_DONE),
            ], 'total')
            ->addSelect([
                'last_order_at' => Order::query()
                    ->select('created_at')
                    ->whereColumn('orders.user_id', 'users.id')
                    ->latest('created_at')
                    ->limit(1),
            ]);
    }

    private function applyStatusFilter(Builder $query, ?string $status): void
    {
        if ($status === 'active') {
            $query->where('account_status', User::ACCOUNT_STATUS_ACTIVE);
        } elseif ($status === 'suspended') {
            $query->where('account_status', User::ACCOUNT_STATUS_SUSPENDED);
        } elseif ($status === 'locked') {
            $query->where('locked_until', '>', now());
        } elseif ($status === 'unverified') {
            $query->whereNull('email_verified_at');
        }
    }

    private function applySegmentFilter(Builder $query, ?string $segment): void
    {
        if ($segment === 'new') {
            $query->doesntHave('orders');
        } elseif ($segment === 'repeat') {
            $query->has('orders', '>=', 2);
        } elseif ($segment === 'vip') {
            $query->whereIn('users.id', Order::query()
                ->select('user_id')
                ->whereNotNull('user_id')
                ->where('status', Order::STATUS_DONE)
                ->groupBy('user_id')
                ->havingRaw('SUM(total) >= ?', [5000000]));
        } elseif ($segment === 'churn_risk') {
            $cutoff = now()->subDays(45);
            $query->whereHas('orders', fn (Builder $orders) => $orders->where('status', Order::STATUS_DONE))
                ->whereDoesntHave('orders', fn (Builder $orders) => $orders
                    ->where('status', '!=', Order::STATUS_CANCELLED)
                    ->where('created_at', '>=', $cutoff));
        }
    }

    private function applySort(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'oldest' => $query->oldest('users.created_at'),
            'spend_desc' => $query->orderByDesc('lifetime_value')->latest('users.created_at'),
            'orders_desc' => $query->orderByDesc('orders_count')->latest('users.created_at'),
            'last_order' => $query->orderByDesc('last_order_at')->latest('users.created_at'),
            default => $query->latest('users.created_at'),
        };
    }

    private function summary(): array
    {
        $vip = $this->baseCustomerQuery();
        $this->applySegmentFilter($vip, 'vip');

        return [
            'total' => $this->baseCustomerQuery()->count(),
            'active' => $this->baseCustomerQuery()->where('account_status', User::ACCOUNT_STATUS_ACTIVE)->count(),
            'unverified' => $this->baseCustomerQuery()->whereNull('email_verified_at')->count(),
            'suspended' => $this->baseCustomerQuery()->where('account_status', User::ACCOUNT_STATUS_SUSPENDED)->count(),
            'locked' => $this->baseCustomerQuery()->where('locked_until', '>', now())->count(),
            'new' => $this->baseCustomerQuery()->doesntHave('orders')->count(),
            'repeat' => $this->baseCustomerQuery()->has('orders', '>=', 2)->count(),
            'vip' => $vip->count(),
        ];
    }

    private function segmentLabel(User $customer): string
    {
        if ((int) ($customer->lifetime_value ?? 0) >= 5000000) {
            return 'VIP';
        }

        if ((int) $customer->orders_count === 0) {
            return 'Chưa mua';
        }

        if ((int) $customer->completed_orders_count > 0
            && $customer->last_order_at
            && now()->diffInDays($customer->last_order_at) >= 45) {
            return 'Nguy cơ rời bỏ';
        }

        return (int) $customer->orders_count >= 2 ? 'Khách quay lại' : 'Khách mới';
    }

    private function orderMetrics(User $customer): array
    {
        $base = $customer->orders();
        $completed = (clone $base)->where('status', Order::STATUS_DONE);
        $totalSpent = (int) (clone $completed)->sum('total');
        $completedCount = (clone $completed)->count();

        return [
            'orders' => (clone $base)->count(),
            'completed' => $completedCount,
            'active' => (clone $base)->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_SHIPPING])->count(),
            'cancelled' => (clone $base)->where('status', Order::STATUS_CANCELLED)->count(),
            'total_spent' => $totalSpent,
            'average_order' => $completedCount > 0 ? (int) round($totalSpent / $completedCount) : 0,
        ];
    }

    private function securityEvents(User $customer)
    {
        if (! Schema::hasTable('security_audit_log')) {
            return collect();
        }

        return DB::table('security_audit_log')
            ->where('user_id', $customer->id)
            ->whereIn('action', ['login_success', 'google_login_success', 'login_failed', 'password_reset_requested', 'logout'])
            ->latest('created_at')
            ->take(12)
            ->get();
    }

    private function failedLoginAttempts(User $customer)
    {
        if (! Schema::hasTable('failed_login_attempts')) {
            return collect();
        }

        return DB::table('failed_login_attempts')
            ->where('email', $customer->email)
            ->latest('attempt_time')
            ->take(8)
            ->get();
    }

    private function customer(User $customer): User
    {
        abort_unless($customer->role === 'customer', 404);

        return $customer;
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
