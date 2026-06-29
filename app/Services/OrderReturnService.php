<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderReturnRequest;
use App\Models\OrderStatusHistory;
use Illuminate\Validation\ValidationException;

class OrderReturnService
{
    private $notifications;

    public function __construct(OrderNotificationService $notifications)
    {
        $this->notifications = $notifications;
    }

    public function createCustomerRequest(Order $order, int $userId, array $payload): OrderReturnRequest
    {
        if (!$order->isReturnRequestable()) {
            throw ValidationException::withMessages([
                'order' => 'Don hang nay khong con trong thoi gian ho tro doi tra/hoan tien.',
            ]);
        }

        $returnRequest = OrderReturnRequest::query()->create([
            'order_id' => $order->id,
            'user_id' => $userId,
            'status' => OrderReturnRequest::STATUS_PENDING,
            'type' => $payload['type'],
            'reason' => $payload['reason'],
            'note' => $payload['note'] ?? null,
            'evidence_path' => $payload['evidence_path'] ?? null,
            'refund_method' => $payload['refund_method'] ?? null,
            'refund_account' => $payload['refund_account'] ?? null,
            'requested_at' => now(),
        ]);

        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'user_id' => $userId,
            'previous_status' => $order->status,
            'status' => 'return_requested',
            'note' => 'Khach gui yeu cau ' . $returnRequest->type_label . '. Ly do: ' . $returnRequest->reason_label . '.',
            'created_at' => now(),
        ]);

        $this->notifications->notifyReturnRequestUpdated($returnRequest, 'requested', $userId);

        return $returnRequest;
    }

    public function approve(OrderReturnRequest $returnRequest, int $actorId, ?string $adminNote, ?int $refundAmount = null): void
    {
        if ($returnRequest->status !== OrderReturnRequest::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'return_request' => 'Yeu cau doi tra nay da duoc xu ly truoc do.',
            ]);
        }

        $returnRequest->loadMissing('order');
        $order = $returnRequest->order;
        $note = $adminNote ?: 'Shop da duyet yeu cau doi tra/hoan tien.';

        $returnRequest->forceFill([
            'status' => OrderReturnRequest::STATUS_APPROVED,
            'resolved_by' => $actorId,
            'resolved_at' => now(),
            'refund_amount' => $returnRequest->type === OrderReturnRequest::TYPE_REFUND
                ? max(0, (int) ($refundAmount ?? $order->total))
                : null,
            'admin_note' => $note,
        ])->save();

        $order->forceFill([
            'admin_note' => $this->appendNoteText($order->admin_note, 'Duyet yeu cau ' . $returnRequest->type_label . ': ' . $note),
        ])->save();

        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'user_id' => $actorId,
            'previous_status' => $order->status,
            'status' => 'return_approved',
            'note' => $note,
            'created_at' => now(),
        ]);

        $this->notifications->notifyReturnRequestUpdated($returnRequest, 'approved', $actorId);
    }

    public function reject(OrderReturnRequest $returnRequest, int $actorId, string $adminNote): void
    {
        if ($returnRequest->status !== OrderReturnRequest::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'return_request' => 'Yeu cau doi tra nay da duoc xu ly truoc do.',
            ]);
        }

        $returnRequest->loadMissing('order');
        $order = $returnRequest->order;

        $returnRequest->forceFill([
            'status' => OrderReturnRequest::STATUS_REJECTED,
            'resolved_by' => $actorId,
            'resolved_at' => now(),
            'admin_note' => $adminNote,
        ])->save();

        $order->forceFill([
            'admin_note' => $this->appendNoteText($order->admin_note, 'Tu choi yeu cau doi tra: ' . $adminNote),
        ])->save();

        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'user_id' => $actorId,
            'previous_status' => $order->status,
            'status' => 'return_rejected',
            'note' => $adminNote,
            'created_at' => now(),
        ]);

        $this->notifications->notifyReturnRequestUpdated($returnRequest, 'rejected', $actorId);
    }

    public function markRefunded(OrderReturnRequest $returnRequest, int $actorId, ?int $refundAmount, ?string $adminNote): void
    {
        if ($returnRequest->type !== OrderReturnRequest::TYPE_REFUND) {
            throw ValidationException::withMessages([
                'return_request' => 'Chi yeu cau hoan tien moi co the danh dau da hoan tien.',
            ]);
        }

        if ($returnRequest->status !== OrderReturnRequest::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'return_request' => 'Can duyet yeu cau hoan tien truoc khi danh dau da hoan tien.',
            ]);
        }

        $returnRequest->loadMissing('order');
        $order = $returnRequest->order;
        $amount = max(0, (int) ($refundAmount ?? $returnRequest->refund_amount ?? $order->total));
        $note = $adminNote ?: 'Shop da hoan tien cho khach.';

        $returnRequest->forceFill([
            'status' => OrderReturnRequest::STATUS_REFUNDED,
            'refund_amount' => $amount,
            'resolved_by' => $actorId,
            'resolved_at' => $returnRequest->resolved_at ?: now(),
            'refunded_at' => now(),
            'admin_note' => $this->appendNoteText($returnRequest->admin_note, $note),
        ])->save();

        $orderPayload = [
            'admin_note' => $this->appendNoteText($order->admin_note, 'Hoan tien ' . number_format($amount, 0, ',', '.') . 'd. ' . $note),
        ];

        if ($amount >= (int) $order->total && $order->payment_status === Order::PAYMENT_STATUS_PAID) {
            $orderPayload['payment_status'] = Order::PAYMENT_STATUS_REFUNDED;
        }

        $order->forceFill($orderPayload)->save();

        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'user_id' => $actorId,
            'previous_status' => $order->status,
            'status' => 'return_refunded',
            'note' => $note . ' So tien: ' . number_format($amount, 0, ',', '.') . 'd.',
            'created_at' => now(),
        ]);

        $this->notifications->notifyReturnRequestUpdated($returnRequest, 'refunded', $actorId);
    }

    private function appendNoteText(?string $existingNote, string $note): string
    {
        $existingNote = trim((string) $existingNote);
        $newNote = '[' . now()->format('d/m/Y H:i') . '] ' . $note;

        return $existingNote !== '' ? $existingNote . PHP_EOL . $newNote : $newNote;
    }
}
