<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use App\Models\UserVoucher;
use App\Notifications\CustomerActivityNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Schema;

class CustomerNotificationService
{
    public function voucherReceived(User $user, Coupon $coupon, UserVoucher $voucher): ?DatabaseNotification
    {
        return $this->send($user, [
            'event_key' => 'voucher-received:' . $voucher->id,
            'category' => 'voucher',
            'icon' => 'ticket',
            'title' => 'Bạn đã nhận voucher ' . $coupon->code,
            'message' => $coupon->benefit_label . '. ' . $coupon->condition_label . '.',
            'action_url' => route('account.profile', ['tab' => 'vouchers']),
            'action_label' => 'Xem kho voucher',
            'related_type' => 'coupon',
            'related_id' => $coupon->id,
        ]);
    }

    public function orderPlaced(Order $order): ?DatabaseNotification
    {
        return $this->sendToOrderUser($order, [
            'event_key' => 'order:' . $order->id . ':placed',
            'category' => 'order',
            'icon' => 'shopping-bag',
            'title' => 'Đã tiếp nhận đơn ' . $order->code,
            'message' => 'Đơn hàng trị giá ' . number_format((int) $order->total, 0, ',', '.') . 'đ đã được ghi nhận.',
            'action_label' => 'Xem đơn hàng',
        ]);
    }

    public function orderStatusChanged(Order $order, string $status): ?DatabaseNotification
    {
        $content = [
            Order::STATUS_CONFIRMED => ['Đơn hàng đã được xác nhận', 'Shop đang chuẩn bị sản phẩm cho đơn ' . $order->code . '.', 'check-circle'],
            Order::STATUS_SHIPPING => ['Đơn hàng đang được giao', 'Đơn ' . $order->code . ' đã rời shop và đang trên đường giao đến bạn.', 'truck'],
            Order::STATUS_DONE => ['Đơn hàng đã hoàn tất', 'Đơn ' . $order->code . ' đã được giao thành công. Cảm ơn bạn đã mua hàng.', 'gift'],
            Order::STATUS_CANCELLED => ['Đơn hàng đã hủy', 'Đơn ' . $order->code . ' đã được hủy. Voucher hợp lệ sẽ được hoàn lại.', 'times-circle'],
        ][$status] ?? null;

        if (!$content) {
            return null;
        }

        return $this->sendToOrderUser($order, [
            'event_key' => 'order:' . $order->id . ':status:' . $status,
            'category' => 'order',
            'icon' => $content[2],
            'title' => $content[0],
            'message' => $content[1],
            'action_label' => 'Theo dõi đơn',
        ]);
    }

    public function paymentReceived(Order $order): ?DatabaseNotification
    {
        return $this->sendToOrderUser($order, [
            'event_key' => 'order:' . $order->id . ':payment:paid',
            'category' => 'payment',
            'icon' => 'credit-card',
            'title' => 'Thanh toán thành công',
            'message' => 'Shop đã nhận thanh toán cho đơn ' . $order->code . '.',
            'action_label' => 'Xem thanh toán',
        ]);
    }

    private function sendToOrderUser(Order $order, array $payload): ?DatabaseNotification
    {
        $user = $order->relationLoaded('user') ? $order->user : $order->user()->first();

        if (!$user) {
            return null;
        }

        return $this->send($user, array_merge($payload, [
            'action_url' => route('checkout.thankyou', [
                'code' => $order->code,
                'token' => $order->public_token,
            ]),
            'related_type' => 'order',
            'related_id' => $order->id,
        ]));
    }

    private function send(User $user, array $payload): ?DatabaseNotification
    {
        if (!Schema::hasTable('notifications')) {
            return null;
        }

        $eventKey = (string) ($payload['event_key'] ?? '');
        if ($eventKey === '') {
            return null;
        }

        $existing = $user->notifications()
            ->where('data->event_key', $eventKey)
            ->first();

        if ($existing) {
            return $existing;
        }

        $user->notify(new CustomerActivityNotification($payload));

        return $user->notifications()
            ->where('data->event_key', $eventKey)
            ->first();
    }
}
