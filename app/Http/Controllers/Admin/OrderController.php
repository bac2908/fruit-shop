<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefundOrderPaymentRequest;
use App\Http\Requests\Admin\ResolveOrderReturnRequest;
use App\Http\Requests\Admin\UpdateOrderShippingRequest;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Requests\Admin\VerifyOrderPaymentRequest;
use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\OrderReturnRequest;
use App\Services\AdminOrderService;
use App\Services\OrderStateTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(Order::statusLabels()))],
            'payment_status' => ['nullable', Rule::in(array_keys(Order::paymentStatusLabels()))],
            'payment_method' => ['nullable', Rule::in(array_keys(Order::paymentMethodLabels()))],
            'attention' => ['nullable', Rule::in(['cancellation', 'return', 'awaiting_payment', 'shipping_setup'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50])],
        ]);

        $query = Order::query()->with(['user', 'cancellationRequests', 'returnRequests']);
        $keyword = trim((string) ($filters['q'] ?? ''));

        if ($keyword !== '') {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $keyword).'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('code', 'like', $like)
                    ->orWhere('customer_name', 'like', $like)
                    ->orWhere('customer_phone', 'like', $like)
                    ->orWhere('customer_email', 'like', $like)
                    ->orWhere('tracking_code', 'like', $like);
            });
        }

        foreach (['status', 'payment_status', 'payment_method'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $attention = $filters['attention'] ?? null;
        if ($attention === 'cancellation') {
            $query->whereHas('cancellationRequests', fn ($inner) => $inner->where('status', OrderCancellationRequest::STATUS_PENDING));
        } elseif ($attention === 'return') {
            $query->whereHas('returnRequests', fn ($inner) => $inner->where('status', OrderReturnRequest::STATUS_PENDING));
        } elseif ($attention === 'awaiting_payment') {
            $query->where('payment_method', Order::PAYMENT_METHOD_BANK_TRANSFER)
                ->where('payment_status', Order::PAYMENT_STATUS_UNPAID);
        } elseif ($attention === 'shipping_setup') {
            $query->where('status', Order::STATUS_CONFIRMED)
                ->where(function ($inner) {
                    $inner->whereNull('shipping_provider')->orWhere('shipping_provider', '');
                });
        }

        $orders = $query
            ->latest('created_at')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->appends($request->query());

        return view('admin.orders', [
            'orders' => $orders,
            'orderSummary' => $this->getOrderSummary(),
            'attentionSummary' => $this->getAttentionSummary(),
            'statusLabels' => Order::statusLabels(),
            'paymentStatusLabels' => Order::paymentStatusLabels(),
            'paymentMethodLabels' => Order::paymentMethodLabels(),
        ]);
    }

    public function show(Order $order, OrderStateTransitionService $stateTransitions): View
    {
        $order->load([
            'user',
            'items.product.images',
            'statusHistories.user',
            'cancellationRequests.user',
            'cancellationRequests.reviewer',
            'returnRequests.user',
            'returnRequests.reviewer',
            'paymentVerifier',
            'refundProcessor',
        ]);

        $availableStatuses = $stateTransitions->availableStatuses($order);
        if (! $order->isReadyForConfirmation()) {
            $availableStatuses = array_values(array_filter(
                $availableStatuses,
                fn (string $status) => $status !== Order::STATUS_CONFIRMED
            ));
        }
        if (trim((string) $order->shipping_provider) === '') {
            $availableStatuses = array_values(array_filter(
                $availableStatuses,
                fn (string $status) => $status !== Order::STATUS_SHIPPING
            ));
        }

        return view('admin.orders.show', [
            'order' => $order,
            'statusLabels' => Order::statusLabels(),
            'availableStatuses' => $availableStatuses,
        ]);
    }

    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Order $order,
        AdminOrderService $orders
    ): RedirectResponse {
        return $this->runAction(
            fn () => $orders->updateStatus(
                $order,
                $request->validated(),
                $request->user()->id,
                $this->auditContext($request)
            ),
            'Đã cập nhật trạng thái đơn hàng.'
        );
    }

    public function updateShipping(
        UpdateOrderShippingRequest $request,
        Order $order,
        AdminOrderService $orders
    ): RedirectResponse {
        return $this->runAction(
            fn () => $orders->updateShipping(
                $order,
                $request->validated(),
                $request->user()->id,
                $this->auditContext($request)
            ),
            'Đã cập nhật phí và thông tin vận chuyển.'
        );
    }

    public function verifyPayment(
        VerifyOrderPaymentRequest $request,
        Order $order,
        AdminOrderService $orders
    ): RedirectResponse {
        return $this->runAction(
            fn () => $orders->verifyBankPayment(
                $order,
                $request->validated(),
                $request->user()->id,
                $this->auditContext($request)
            ),
            'Đã xác minh thanh toán chuyển khoản.'
        );
    }

    public function refundPayment(
        RefundOrderPaymentRequest $request,
        Order $order,
        AdminOrderService $orders
    ): RedirectResponse {
        return $this->runAction(
            fn () => $orders->refundCancelledPayment(
                $order,
                $request->validated(),
                $request->user()->id,
                $this->auditContext($request)
            ),
            'Đã ghi nhận hoàn tiền cho đơn bị hủy.'
        );
    }

    public function approveCancellation(
        Request $request,
        OrderCancellationRequest $cancellationRequest,
        AdminOrderService $orders
    ): RedirectResponse {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500', 'not_regex:/[<>]/'],
        ]);

        return $this->runAction(
            fn () => $orders->approveCancellation(
                $cancellationRequest,
                $this->cleanText($validated['admin_note'] ?? null),
                $request->user()->id,
                $this->auditContext($request)
            ),
            'Đã duyệt yêu cầu hủy và hoàn lại tồn kho.'
        );
    }

    public function rejectCancellation(
        Request $request,
        OrderCancellationRequest $cancellationRequest,
        AdminOrderService $orders
    ): RedirectResponse {
        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'min:5', 'max:500', 'not_regex:/[<>]/'],
        ], [
            'admin_note.required' => 'Vui lòng nhập lý do từ chối để khách hàng hiểu.',
            'admin_note.min' => 'Lý do từ chối cần có ít nhất 5 ký tự.',
        ]);

        return $this->runAction(
            fn () => $orders->rejectCancellation(
                $cancellationRequest,
                $this->cleanText($validated['admin_note']),
                $request->user()->id,
                $this->auditContext($request)
            ),
            'Đã từ chối yêu cầu hủy đơn.'
        );
    }

    public function approveReturn(
        ResolveOrderReturnRequest $request,
        OrderReturnRequest $returnRequest,
        AdminOrderService $orders
    ): RedirectResponse {
        return $this->runAction(
            fn () => $orders->approveReturn(
                $returnRequest,
                $request->validated(),
                $request->user()->id,
                $this->auditContext($request)
            ),
            'Đã duyệt yêu cầu đổi trả.'
        );
    }

    public function rejectReturn(
        ResolveOrderReturnRequest $request,
        OrderReturnRequest $returnRequest,
        AdminOrderService $orders
    ): RedirectResponse {
        return $this->runAction(
            fn () => $orders->rejectReturn(
                $returnRequest,
                $request->validated(),
                $request->user()->id,
                $this->auditContext($request)
            ),
            'Đã từ chối yêu cầu đổi trả.'
        );
    }

    public function refundReturn(
        ResolveOrderReturnRequest $request,
        OrderReturnRequest $returnRequest,
        AdminOrderService $orders
    ): RedirectResponse {
        return $this->runAction(
            fn () => $orders->refundReturn(
                $returnRequest,
                $request->validated(),
                $request->user()->id,
                $this->auditContext($request)
            ),
            'Đã ghi nhận hoàn tiền cho yêu cầu đổi trả.'
        );
    }

    public function completeReturn(
        ResolveOrderReturnRequest $request,
        OrderReturnRequest $returnRequest,
        AdminOrderService $orders
    ): RedirectResponse {
        return $this->runAction(
            fn () => $orders->completeReturn(
                $returnRequest,
                $request->validated(),
                $request->user()->id,
                $this->auditContext($request)
            ),
            'Đã xác nhận hoàn tất đổi sản phẩm.'
        );
    }

    private function runAction(callable $action, string $successMessage): RedirectResponse
    {
        try {
            $action();
        } catch (ValidationException $exception) {
            return back()
                ->withInput()
                ->withErrors($exception->errors())
                ->with('error', collect($exception->errors())->flatten()->first());
        }

        return back()->with('success', $successMessage);
    }

    private function getOrderSummary(): array
    {
        $summary = array_fill_keys(array_keys(Order::statusLabels()), 0);
        Order::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')
            ->pluck('total', 'status')
            ->each(function ($total, $status) use (&$summary) {
                $summary[$status] = (int) $total;
            });

        return $summary;
    }

    private function getAttentionSummary(): array
    {
        return [
            'cancellation' => OrderCancellationRequest::query()->where('status', OrderCancellationRequest::STATUS_PENDING)->count(),
            'return' => OrderReturnRequest::query()->where('status', OrderReturnRequest::STATUS_PENDING)->count(),
            'awaiting_payment' => Order::query()
                ->where('payment_method', Order::PAYMENT_METHOD_BANK_TRANSFER)
                ->where('payment_status', Order::PAYMENT_STATUS_UNPAID)
                ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_DONE])
                ->count(),
            'shipping_setup' => Order::query()
                ->where('status', Order::STATUS_CONFIRMED)
                ->where(function ($query) {
                    $query->whereNull('shipping_provider')->orWhere('shipping_provider', '');
                })
                ->count(),
        ];
    }

    private function cleanText($value): ?string
    {
        $value = preg_replace('/\s+/u', ' ', trim((string) $value));

        return $value === '' ? null : $value;
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
