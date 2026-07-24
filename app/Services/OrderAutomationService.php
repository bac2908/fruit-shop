<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Support\LocalDateTime;

class OrderAutomationService
{
    private $notifications;

    private $customerNotifications;

    private $stateTransitions;

    public function __construct(
        OrderNotificationService $notifications,
        CustomerNotificationService $customerNotifications,
        OrderStateTransitionService $stateTransitions
    ) {
        $this->notifications = $notifications;
        $this->customerNotifications = $customerNotifications;
        $this->stateTransitions = $stateTransitions;
    }

    public function autoConfirmAfterStockReserved(Order $order, ?int $actorId = null, ?string $reason = null): bool
    {
        if (! config('shop.order_automation.auto_confirm_stock_reserved', true)) {
            return false;
        }

        if (! $order->isReadyForConfirmation()) {
            return false;
        }

        $note = $reason ?: 'He thong tu dong xac nhan vi don da giu ton kho thanh cong.';

        $this->stateTransitions->transition($order, Order::STATUS_CONFIRMED, $actorId, $note, [
            'shipping_status' => Order::SHIPPING_STATUS_PREPARING,
        ]);

        $order->refresh();
        $this->notifications->notifyOrderConfirmed($order, $actorId);

        return true;
    }

    public function autoMarkPaymentCollectedOnCompletion(Order $order, ?int $actorId = null): bool
    {
        if (! config('shop.order_automation.auto_mark_cod_paid_on_done', true)) {
            return false;
        }

        if ($order->payment_method !== Order::PAYMENT_METHOD_COD) {
            return false;
        }

        if ($order->status !== Order::STATUS_DONE || $order->payment_status === Order::PAYMENT_STATUS_PAID) {
            return false;
        }

        $note = 'He thong tu dong danh dau COD da thu tien khi don hoan tat.';

        $order->forceFill([
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'paid_at' => $order->paid_at ?: now(),
            'admin_note' => $this->appendNoteText($order->admin_note, $note),
        ])->save();

        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'user_id' => $actorId,
            'previous_status' => Order::STATUS_DONE,
            'status' => 'payment_collected',
            'note' => $note,
            'created_at' => now(),
        ]);

        $this->customerNotifications->paymentReceived($order->refresh());

        return true;
    }

    private function appendNoteText(?string $existingNote, string $note): string
    {
        $existingNote = trim((string) $existingNote);
        $newNote = '['.LocalDateTime::format(now()).'] '.$note;

        return $existingNote !== '' ? $existingNote.PHP_EOL.$newNote : $newNote;
    }
}
