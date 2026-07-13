<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderReturnRequest;
use App\Models\OrderStatusHistory;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\OrderConfirmedNotification;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\OrderReturnRequestNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class OrderNotificationService
{
    private const HISTORY_PLACED_EMAIL_SENT = 'placed_email_sent';
    private const HISTORY_PLACED_EMAIL_FAILED = 'placed_email_failed';
    private const HISTORY_CONFIRMED_EMAIL_SENT = 'confirmed_email_sent';
    private const HISTORY_CONFIRMED_EMAIL_FAILED = 'confirmed_email_failed';
    private const HISTORY_CANCELLED_EMAIL_SENT = 'cancelled_email_sent';
    private const HISTORY_CANCELLED_EMAIL_FAILED = 'cancelled_email_failed';

    private $customerNotifications;

    public function __construct(CustomerNotificationService $customerNotifications)
    {
        $this->customerNotifications = $customerNotifications;
    }

    public function notifyOrderPlaced(Order $order, ?int $actorId = null): bool
    {
        if (!config('shop.order_automation.order_placed_email_enabled', true)) {
            return false;
        }

        if ($this->placedEmailAlreadySent($order->id)) {
            return false;
        }

        $callback = function () use ($order, $actorId) {
            $freshOrder = Order::query()
                ->with(['items', 'user'])
                ->whereKey($order->id)
                ->first();

            if (!$freshOrder || $freshOrder->status === Order::STATUS_CANCELLED) {
                return;
            }

            if ($this->placedEmailAlreadySent($freshOrder->id)) {
                return;
            }

            $email = trim((string) ($freshOrder->customer_email ?: optional($freshOrder->user)->email));
            if ($email === '') {
                $this->recordEmailHistory(
                    $freshOrder,
                    $actorId,
                    self::HISTORY_PLACED_EMAIL_FAILED,
                    'Khong gui duoc email tiep nhan vi don hang khong co email khach.'
                );

                return;
            }

            try {
                Notification::route('mail', $email)
                    ->notify(new OrderPlacedNotification($freshOrder));

                $this->recordEmailHistory(
                    $freshOrder,
                    $actorId,
                    self::HISTORY_PLACED_EMAIL_SENT,
                    'Da gui email tiep nhan don hang den ' . $email . '.'
                );
            } catch (Throwable $exception) {
                Log::warning('Cannot send order placed email.', [
                    'order_id' => $freshOrder->id,
                    'order_code' => $freshOrder->code,
                    'email' => $email,
                    'error' => $exception->getMessage(),
                ]);

                $this->recordEmailHistory(
                    $freshOrder,
                    $actorId,
                    self::HISTORY_PLACED_EMAIL_FAILED,
                    'Gui email tiep nhan don hang that bai: ' . $exception->getMessage()
                );
            }
        };

        if (DB::connection()->transactionLevel() > 0) {
            DB::afterCommit($callback);
        } else {
            $callback();
        }

        return true;
    }

    public function notifyOrderConfirmed(Order $order, ?int $actorId = null): bool
    {
        if ($order->status !== Order::STATUS_CONFIRMED) {
            return false;
        }

        $this->customerNotifications->orderStatusChanged($order, Order::STATUS_CONFIRMED);

        if (!config('shop.order_automation.order_confirmed_email_enabled', true)) {
            return true;
        }

        if ($this->confirmedEmailAlreadySent($order->id)) {
            return false;
        }

        $callback = function () use ($order, $actorId) {
            $freshOrder = Order::query()
                ->with(['items', 'user'])
                ->whereKey($order->id)
                ->first();

            if (!$freshOrder || $freshOrder->status !== Order::STATUS_CONFIRMED) {
                return;
            }

            if ($this->confirmedEmailAlreadySent($freshOrder->id)) {
                return;
            }

            $email = trim((string) ($freshOrder->customer_email ?: optional($freshOrder->user)->email));
            if ($email === '') {
                $this->recordEmailHistory($freshOrder, $actorId, self::HISTORY_CONFIRMED_EMAIL_FAILED, 'Khong gui duoc email xac nhan vi don hang khong co email khach.');
                return;
            }

            try {
                Notification::route('mail', $email)
                    ->notify(new OrderConfirmedNotification($freshOrder));

                $this->recordEmailHistory($freshOrder, $actorId, self::HISTORY_CONFIRMED_EMAIL_SENT, 'Da gui email thong bao don hang duoc xac nhan den ' . $email . '.');
            } catch (Throwable $exception) {
                Log::warning('Cannot send order confirmed email.', [
                    'order_id' => $freshOrder->id,
                    'order_code' => $freshOrder->code,
                    'email' => $email,
                    'error' => $exception->getMessage(),
                ]);

                $this->recordEmailHistory($freshOrder, $actorId, self::HISTORY_CONFIRMED_EMAIL_FAILED, 'Gui email xac nhan don hang that bai: ' . $exception->getMessage());
            }
        };

        if (DB::connection()->transactionLevel() > 0) {
            DB::afterCommit($callback);
        } else {
            $callback();
        }

        return true;
    }

    public function notifyReturnRequestUpdated(OrderReturnRequest $returnRequest, string $event, ?int $actorId = null): bool
    {
        if (!config('shop.returns.email_enabled', true)) {
            return false;
        }

        if (!in_array($event, ['requested', 'approved', 'rejected', 'refunded'], true)) {
            return false;
        }

        $sentStatus = 'return_' . $event . '_email_sent';
        $failedStatus = 'return_' . $event . '_email_failed';

        if ($this->returnEmailAlreadySent($returnRequest->order_id, $sentStatus)) {
            return false;
        }

        $callback = function () use ($returnRequest, $event, $actorId, $sentStatus, $failedStatus) {
            $freshRequest = OrderReturnRequest::query()
                ->with(['order.user'])
                ->whereKey($returnRequest->id)
                ->first();

            if (!$freshRequest || !$freshRequest->order) {
                return;
            }

            if ($this->returnEmailAlreadySent($freshRequest->order_id, $sentStatus)) {
                return;
            }

            $order = $freshRequest->order;
            $email = trim((string) ($order->customer_email ?: optional($order->user)->email));
            if ($email === '') {
                $this->recordEmailHistory($order, $actorId, $failedStatus, 'Khong gui duoc email doi tra vi don hang khong co email khach.');
                return;
            }

            try {
                Notification::route('mail', $email)
                    ->notify(new OrderReturnRequestNotification($freshRequest, $event));

                $this->recordEmailHistory($order, $actorId, $sentStatus, 'Da gui email cap nhat yeu cau doi tra den ' . $email . '.');
            } catch (Throwable $exception) {
                Log::warning('Cannot send return request email.', [
                    'order_id' => $order->id,
                    'order_code' => $order->code,
                    'return_request_id' => $freshRequest->id,
                    'event' => $event,
                    'email' => $email,
                    'error' => $exception->getMessage(),
                ]);

                $this->recordEmailHistory($order, $actorId, $failedStatus, 'Gui email doi tra that bai: ' . $exception->getMessage());
            }
        };

        if (DB::connection()->transactionLevel() > 0) {
            DB::afterCommit($callback);
        } else {
            $callback();
        }

        return true;
    }

    public function notifyOrderCancelled(Order $order, ?int $actorId = null, ?string $reason = null): bool
    {
        if ($order->status !== Order::STATUS_CANCELLED) {
            return false;
        }

        $this->customerNotifications->orderStatusChanged($order, Order::STATUS_CANCELLED);

        if (!config('shop.order_automation.order_cancelled_email_enabled', true)) {
            return true;
        }

        if ($this->cancelledEmailAlreadySent($order->id)) {
            return false;
        }

        $callback = function () use ($order, $actorId, $reason) {
            $freshOrder = Order::query()
                ->with(['items', 'user'])
                ->whereKey($order->id)
                ->first();

            if (!$freshOrder || $freshOrder->status !== Order::STATUS_CANCELLED) {
                return;
            }

            if ($this->cancelledEmailAlreadySent($freshOrder->id)) {
                return;
            }

            $email = trim((string) ($freshOrder->customer_email ?: optional($freshOrder->user)->email));
            if ($email === '') {
                $this->recordEmailHistory($freshOrder, $actorId, self::HISTORY_CANCELLED_EMAIL_FAILED, 'Khong gui duoc email huy don vi don hang khong co email khach.');
                return;
            }

            try {
                Notification::route('mail', $email)
                    ->notify(new OrderCancelledNotification($freshOrder, $reason));

                $this->recordEmailHistory($freshOrder, $actorId, self::HISTORY_CANCELLED_EMAIL_SENT, 'Da gui email thong bao huy don den ' . $email . '.');
            } catch (Throwable $exception) {
                Log::warning('Cannot send order cancelled email.', [
                    'order_id' => $freshOrder->id,
                    'order_code' => $freshOrder->code,
                    'email' => $email,
                    'error' => $exception->getMessage(),
                ]);

                $this->recordEmailHistory($freshOrder, $actorId, self::HISTORY_CANCELLED_EMAIL_FAILED, 'Gui email huy don that bai: ' . $exception->getMessage());
            }
        };

        if (DB::connection()->transactionLevel() > 0) {
            DB::afterCommit($callback);
        } else {
            $callback();
        }

        return true;
    }

    private function confirmedEmailAlreadySent(int $orderId): bool
    {
        return OrderStatusHistory::query()
            ->where('order_id', $orderId)
            ->where('status', self::HISTORY_CONFIRMED_EMAIL_SENT)
            ->exists();
    }

    private function placedEmailAlreadySent(int $orderId): bool
    {
        return OrderStatusHistory::query()
            ->where('order_id', $orderId)
            ->where('status', self::HISTORY_PLACED_EMAIL_SENT)
            ->exists();
    }

    private function cancelledEmailAlreadySent(int $orderId): bool
    {
        return OrderStatusHistory::query()
            ->where('order_id', $orderId)
            ->where('status', self::HISTORY_CANCELLED_EMAIL_SENT)
            ->exists();
    }

    private function returnEmailAlreadySent(int $orderId, string $status): bool
    {
        return OrderStatusHistory::query()
            ->where('order_id', $orderId)
            ->where('status', $status)
            ->exists();
    }

    private function recordEmailHistory(Order $order, ?int $actorId, string $status, string $note): void
    {
        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'user_id' => $actorId,
            'previous_status' => $order->status,
            'status' => $status,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}
