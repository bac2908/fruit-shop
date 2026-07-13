<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\OrderStatusHistory;
use App\Services\OrderCancellationService;
use App\Services\OrderAutomationService;
use App\Services\OrderNotificationService;
use App\Services\CustomerNotificationService;
use App\Services\OrderStateTransitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(OrderStateTransitionService $stateTransitions)
    {
        $orders = Order::query()
            ->with(['cancellationRequests.user'])
            ->latest()
            ->take(80)
            ->get();

        return view('admin.orders', [
            'orders' => $orders,
            'orderSummary' => $this->getOrderSummary(),
            'pendingCancellationCount' => OrderCancellationRequest::query()
                ->where('status', OrderCancellationRequest::STATUS_PENDING)
                ->count(),
            'statusLabels' => Order::statusLabels(),
            'allowedStatusTransitions' => $orders->mapWithKeys(function (Order $order) use ($stateTransitions) {
                return [$order->id => $stateTransitions->availableStatuses($order)];
            }),
            'paymentStatusLabels' => Order::paymentStatusLabels(),
            'cancellationStatusLabels' => OrderCancellationRequest::statusLabels(),
        ]);
    }

    public function updateStatus(
        Request $request,
        Order $order,
        OrderCancellationService $cancellationService,
        OrderAutomationService $orderAutomation,
        OrderNotificationService $orderNotifications,
        CustomerNotificationService $customerNotifications,
        OrderStateTransitionService $stateTransitions
    )
    {
        $request->merge([
            'admin_note' => $this->cleanTextInput($request->input('admin_note'), true),
        ]);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::statusLabels()))],
            'admin_note' => ['nullable', 'string', 'max:500', 'not_regex:/[<>]/'],
        ], [
            'status.required' => 'Vui long chon trang thai don hang.',
            'status.in' => 'Trang thai don hang khong hop le.',
            'admin_note.max' => 'Ghi chu khong duoc vuot qua 500 ky tu.',
            'admin_note.not_regex' => 'Ghi chu khong duoc chua ky tu HTML.',
        ]);

        try {
            DB::transaction(function () use ($request, $order, $validated, $cancellationService, $orderAutomation, $orderNotifications, $customerNotifications, $stateTransitions) {
                $order = Order::query()
                    ->with(['items', 'cancellationRequests'])
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($order->status === $validated['status']) {
                    if ($validated['status'] === Order::STATUS_DONE) {
                        $orderAutomation->autoMarkPaymentCollectedOnCompletion($order, $request->user()->id);
                    }

                    if ($validated['status'] === Order::STATUS_CONFIRMED) {
                        $orderNotifications->notifyOrderConfirmed($order, $request->user()->id);
                    } else {
                        $customerNotifications->orderStatusChanged($order->refresh(), $validated['status']);
                    }

                    return;
                }

                $stateTransitions->ensureCanTransition($order, $validated['status']);

                if (
                    $order->hasPendingCancellationRequest()
                    && in_array($validated['status'], [Order::STATUS_SHIPPING, Order::STATUS_DONE], true)
                ) {
                    throw ValidationException::withMessages([
                        'status' => 'Don dang co yeu cau huy. Hay duyet hoac tu choi yeu cau huy truoc khi giao hang.',
                    ]);
                }

                if ($validated['status'] === Order::STATUS_CANCELLED) {
                    $cancellationService->cancelImmediately(
                        $order,
                        $request->user()->id,
                        null,
                        $validated['admin_note'] ?: 'Admin huy don tu man hinh quan tri.',
                        true
                    );

                    return;
                }

                $statusNote = $validated['admin_note']
                    ?: 'Admin cap nhat trang thai tu ' . $order->status . ' sang ' . $validated['status'] . '.';

                $stateTransitions->transition(
                    $order,
                    $validated['status'],
                    $request->user()->id,
                    $statusNote
                );

                if ($validated['status'] === Order::STATUS_DONE) {
                    $orderAutomation->autoMarkPaymentCollectedOnCompletion($order, $request->user()->id);
                }

                if ($validated['status'] === Order::STATUS_CONFIRMED) {
                    $order->refresh();
                    $orderNotifications->notifyOrderConfirmed($order, $request->user()->id);
                } else {
                    $customerNotifications->orderStatusChanged($order->refresh(), $validated['status']);
                }
            });
        } catch (ValidationException $exception) {
            return redirect()->route('admin.orders')->with('error', collect($exception->errors())->flatten()->first() ?? 'Khong the cap nhat don hang.');
        }

        return redirect()->route('admin.orders')->with('success', 'Da cap nhat trang thai don hang.');
    }

    public function approveCancellation(
        Request $request,
        OrderCancellationRequest $cancellationRequest,
        OrderCancellationService $cancellationService
    ) {
        $request->merge([
            'admin_note' => $this->cleanTextInput($request->input('admin_note'), true),
        ]);

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500', 'not_regex:/[<>]/'],
        ]);

        DB::transaction(function () use ($request, $cancellationRequest, $validated, $cancellationService) {
            $cancellationRequest = OrderCancellationRequest::query()
                ->whereKey($cancellationRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($cancellationRequest->status !== OrderCancellationRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'cancellation' => 'Yeu cau huy nay da duoc xu ly truoc do.',
                ]);
            }

            $order = Order::query()
                ->with('items')
                ->whereKey($cancellationRequest->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            $cancellationRequest->forceFill([
                'status' => OrderCancellationRequest::STATUS_APPROVED,
                'resolved_by' => $request->user()->id,
                'resolved_at' => now(),
                'admin_note' => $validated['admin_note'] ?: 'Shop da duyet yeu cau huy don.',
            ])->save();

            $cancellationService->cancelImmediately(
                $order,
                $request->user()->id,
                $cancellationRequest,
                'Shop duyet yeu cau huy don. Ly do: ' . $cancellationRequest->reason_label,
                true
            );
        });

        return redirect()->route('admin.orders')->with('success', 'Da duyet yeu cau huy va cap nhat don hang.');
    }

    public function rejectCancellation(Request $request, OrderCancellationRequest $cancellationRequest)
    {
        $request->merge([
            'admin_note' => $this->cleanTextInput($request->input('admin_note'), true),
        ]);

        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'min:5', 'max:500', 'not_regex:/[<>]/'],
        ], [
            'admin_note.required' => 'Vui long nhap ly do tu choi de khach hang hieu.',
            'admin_note.min' => 'Ly do tu choi can co it nhat 5 ky tu.',
            'admin_note.max' => 'Ly do tu choi khong duoc vuot qua 500 ky tu.',
            'admin_note.not_regex' => 'Ly do tu choi khong duoc chua ky tu HTML.',
        ]);

        DB::transaction(function () use ($request, $cancellationRequest, $validated) {
            $cancellationRequest = OrderCancellationRequest::query()
                ->whereKey($cancellationRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($cancellationRequest->status !== OrderCancellationRequest::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'cancellation' => 'Yeu cau huy nay da duoc xu ly truoc do.',
                ]);
            }

            $order = Order::query()
                ->whereKey($cancellationRequest->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            $rejectNote = 'Shop tu choi yeu cau huy don: ' . $validated['admin_note'];

            $cancellationRequest->forceFill([
                'status' => OrderCancellationRequest::STATUS_REJECTED,
                'resolved_by' => $request->user()->id,
                'resolved_at' => now(),
                'admin_note' => $validated['admin_note'],
            ])->save();

            $order->forceFill([
                'admin_note' => $this->appendNoteText($order->admin_note, $rejectNote),
            ])->save();

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'user_id' => $request->user()->id,
                'previous_status' => $order->status,
                'status' => 'cancel_rejected',
                'note' => $rejectNote,
                'created_at' => now(),
            ]);
        });

        return redirect()->route('admin.orders')->with('success', 'Da tu choi yeu cau huy don.');
    }

    public function updateShipping(
        Request $request,
        Order $order,
        OrderNotificationService $orderNotifications,
        OrderStateTransitionService $stateTransitions
    )
    {
        $request->merge([
            'shipping_delivery_note' => $this->cleanTextInput($request->input('shipping_delivery_note'), true),
        ]);

        $validated = $request->validate([
            'shipping_fee' => ['required', 'integer', 'min:0', 'max:2000000'],
            'shipping_delivery_note' => ['nullable', 'string', 'max:500', 'not_regex:/[<>]/'],
        ], [
            'shipping_fee.required' => 'Vui long nhap phi giao hang.',
            'shipping_fee.integer' => 'Phi giao hang phai la so nguyen VND.',
            'shipping_fee.min' => 'Phi giao hang khong duoc am.',
            'shipping_fee.max' => 'Phi giao hang qua lon, vui long kiem tra lai.',
            'shipping_delivery_note.max' => 'Ghi chu giao hang khong duoc vuot qua 500 ky tu.',
            'shipping_delivery_note.not_regex' => 'Ghi chu giao hang khong duoc chua ky tu HTML.',
        ]);

        try {
            DB::transaction(function () use ($request, $order, $validated, $orderNotifications, $stateTransitions) {
                $order = Order::query()
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($order->status === Order::STATUS_CANCELLED) {
                    throw ValidationException::withMessages([
                        'shipping_fee' => 'Don da huy nen khong the chot phi giao hang.',
                    ]);
                }

                $previousStatus = $order->status;
                $previousShippingFee = (int) $order->shipping_fee;
                $shippingFee = (int) $validated['shipping_fee'];
                $total = max(0, (int) $order->subtotal + $shippingFee - (int) $order->discount_total);

                if (
                    $order->payment_status === Order::PAYMENT_STATUS_PAID
                    && ($shippingFee !== $previousShippingFee || $total !== (int) $order->total)
                ) {
                    throw ValidationException::withMessages([
                        'shipping_fee' => 'Đơn đã thanh toán nên không thể thay đổi phí giao hàng hoặc tổng tiền.',
                    ]);
                }
                $note = $validated['shipping_delivery_note']
                    ?: 'Admin chot phi giao hang: ' . number_format($shippingFee, 0, ',', '.') . ' VND.';

                $changes = [
                    'shipping_fee' => $shippingFee,
                    'shipping_fee_status' => Order::SHIPPING_FEE_STATUS_CONFIRMED,
                    'shipping_delivery_note' => $note,
                    'total' => $total,
                    'admin_note' => $this->appendNoteText(
                        $order->admin_note,
                        'Chot phi ship tu ' . number_format($previousShippingFee, 0, ',', '.') . ' VND sang ' . number_format($shippingFee, 0, ',', '.') . ' VND. ' . $note
                    ),
                ];

                $shouldAutoConfirm = $order->status === Order::STATUS_PENDING
                    && !$order->hasPendingCancellationRequest()
                    && (
                        $order->payment_method === Order::PAYMENT_METHOD_COD
                        || $order->payment_status === Order::PAYMENT_STATUS_PAID
                    );

                $order->forceFill($changes)->save();

                if ($shouldAutoConfirm) {
                    $stateTransitions->transition(
                        $order,
                        Order::STATUS_CONFIRMED,
                        $request->user()->id,
                        'Shop da chot phi giao hang: ' . number_format($shippingFee, 0, ',', '.') . ' VND.'
                    );
                    $order->refresh();
                    $orderNotifications->notifyOrderConfirmed($order, $request->user()->id);
                } else {
                    OrderStatusHistory::query()->create([
                        'order_id' => $order->id,
                        'user_id' => $request->user()->id,
                        'previous_status' => $previousStatus,
                        'status' => 'shipping_fee_confirmed',
                        'note' => 'Shop da chot phi giao hang: ' . number_format($shippingFee, 0, ',', '.') . ' VND.',
                        'created_at' => now(),
                    ]);
                }
            });
        } catch (ValidationException $exception) {
            return redirect()->route('admin.orders')->with('error', collect($exception->errors())->flatten()->first() ?? 'Khong the chot phi giao hang.');
        }

        return redirect()->route('admin.orders')->with('success', 'Da chot phi giao hang va cap nhat tong tien don.');
    }

    private function getOrderSummary(): array
    {
        $summary = array_fill_keys(array_keys(Order::statusLabels()), 0);

        Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->each(function ($total, $status) use (&$summary) {
                $summary[$status] = (int) $total;
            });

        return $summary;
    }

    private function cleanTextInput($value, bool $nullable = false): ?string
    {
        $cleaned = preg_replace('/\s+/u', ' ', trim((string) $value));

        if ($nullable && $cleaned === '') {
            return null;
        }

        return $cleaned;
    }

    private function appendNoteText(?string $existingNote, string $note): string
    {
        $existingNote = trim((string) $existingNote);
        $newNote = '[' . now()->format('d/m/Y H:i') . '] ' . $note;

        return $existingNote !== '' ? $existingNote . PHP_EOL . $newNote : $newNote;
    }
}
