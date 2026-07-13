<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPlacedNotification extends Notification
{
    private $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $order = $this->order->loadMissing('items');
        $orderUrl = route('checkout.thankyou', [
            'code' => $order->code,
            'token' => $order->public_token,
        ]);

        $mail = (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Đã tiếp nhận đơn hàng ' . $order->code)
            ->greeting('Xin chào ' . ($order->customer_name ?: 'bạn') . ',')
            ->line('Thế Giới Trái Cây đã tiếp nhận đơn hàng ' . $order->code . ' của bạn.')
            ->line('Tổng thanh toán: ' . number_format((int) $order->total, 0, ',', '.') . 'đ.')
            ->line('Phương thức thanh toán: ' . $order->payment_method_label . '.')
            ->line('Địa chỉ giao hàng: ' . ($order->shipping_address ?: 'Shop sẽ liên hệ xác nhận.'));

        if ($order->requiresShippingConfirmation()) {
            $mail->line('Phí giao hàng hiện là tạm tính. Shop sẽ xác nhận phí cuối cùng trước khi giao.');
        }

        return $mail
            ->action('Xem chi tiết đơn hàng', $orderUrl)
            ->line('Shop sẽ gửi email tiếp theo khi đơn được xác nhận hoặc có thay đổi trạng thái.');
    }
}
