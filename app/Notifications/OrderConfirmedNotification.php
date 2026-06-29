<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmedNotification extends Notification
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
        $thankYouUrl = route('checkout.thankyou', [
            'code' => $order->code,
            'token' => $order->public_token,
        ]);

        return (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Don hang ' . $order->code . ' da duoc xac nhan')
            ->greeting('Xin chao ' . ($order->customer_name ?: 'ban') . ',')
            ->line('Don hang ' . $order->code . ' cua ban da duoc shop xac nhan va dang chuan bi hang.')
            ->line('Tong thanh toan: ' . number_format((int) $order->total, 0, ',', '.') . 'd.')
            ->line('Phuong thuc thanh toan: ' . $order->payment_method_label . '.')
            ->line('Trang thai thanh toan: ' . $order->payment_status_label . '.')
            ->action('Xem chi tiet don hang', $thankYouUrl)
            ->line('Cam on ban da mua hang tai The Gioi Trai Cay.');
    }
}
