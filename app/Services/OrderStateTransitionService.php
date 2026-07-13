<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Validation\ValidationException;

class OrderStateTransitionService
{
    private const TRANSITIONS = [
        Order::STATUS_PENDING => [Order::STATUS_CONFIRMED, Order::STATUS_CANCELLED],
        Order::STATUS_CONFIRMED => [Order::STATUS_SHIPPING, Order::STATUS_CANCELLED],
        Order::STATUS_SHIPPING => [Order::STATUS_DONE],
        Order::STATUS_DONE => [],
        Order::STATUS_CANCELLED => [],
    ];

    public function canTransition(Order $order, string $nextStatus): bool
    {
        if ($order->status === $nextStatus) {
            return true;
        }

        return in_array($nextStatus, self::TRANSITIONS[$order->status] ?? [], true);
    }

    public function availableStatuses(Order $order): array
    {
        return array_values(array_unique(array_merge(
            [$order->status],
            self::TRANSITIONS[$order->status] ?? []
        )));
    }

    public function ensureCanTransition(Order $order, string $nextStatus): void
    {
        if ($this->canTransition($order, $nextStatus)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => sprintf(
                'Không thể chuyển đơn từ "%s" sang "%s".',
                $order->status_label,
                Order::statusLabels()[$nextStatus] ?? $nextStatus
            ),
        ]);
    }

    public function transition(
        Order $order,
        string $nextStatus,
        ?int $actorId,
        string $note,
        array $attributes = []
    ): bool {
        if ($order->status === $nextStatus) {
            return false;
        }

        $this->ensureCanTransition($order, $nextStatus);
        $previousStatus = $order->status;

        $order->forceFill(array_merge($attributes, [
            'status' => $nextStatus,
            'shipping_status' => $attributes['shipping_status'] ?? $this->shippingStatus($nextStatus, $order->shipping_status),
            'admin_note' => $this->appendNote($order->admin_note, $note),
        ]))->save();

        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'user_id' => $actorId,
            'previous_status' => $previousStatus,
            'status' => $nextStatus,
            'note' => $note,
            'created_at' => now(),
        ]);

        return true;
    }

    private function shippingStatus(string $status, string $currentStatus): string
    {
        return [
            Order::STATUS_PENDING => Order::SHIPPING_STATUS_PENDING,
            Order::STATUS_CONFIRMED => Order::SHIPPING_STATUS_PREPARING,
            Order::STATUS_SHIPPING => Order::SHIPPING_STATUS_SHIPPING,
            Order::STATUS_DONE => Order::SHIPPING_STATUS_DELIVERED,
            Order::STATUS_CANCELLED => Order::SHIPPING_STATUS_FAILED,
        ][$status] ?? $currentStatus;
    }

    private function appendNote(?string $existingNote, string $note): string
    {
        $existingNote = trim((string) $existingNote);
        $newNote = '[' . now()->format('d/m/Y H:i') . '] ' . $note;

        return $existingNote !== '' ? $existingNote . PHP_EOL . $newNote : $newNote;
    }
}
