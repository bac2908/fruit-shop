<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCancelledNotification extends Notification
{
    private $order;
    private $reason;

    public function __construct(Order $order, ?string $reason = null)
    {
        $this->order = $order;
        $this->reason = $reason;
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

        $mail = (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Don hang ' . $order->code . ' da duoc huy')
            ->greeting('Xin chao ' . ($order->customer_name ?: 'ban') . ',')
            ->line('Don hang ' . $order->code . ' cua ban da duoc huy thanh cong.')
            ->line('Tong gia tri don hang: ' . number_format((int) $order->total, 0, ',', '.') . 'd.')
            ->line('Phuong thuc thanh toan: ' . $order->payment_method_label . '.')
            ->line('Trang thai thanh toan hien tai: ' . $order->payment_status_label . '.');

        if ($this->reason) {
            $mail->line('Ly do/ghi chu: ' . $this->reason);
        }

        if ($order->payment_status === Order::PAYMENT_STATUS_REFUNDED) {
            $mail->line('Don hang da duoc danh dau hoan tien. Shop se lien he neu can them thong tin doi soat.');
        }

        return $mail
            ->action('Xem chi tiet don hang', $thankYouUrl)
            ->line('Neu ban can ho tro them, vui long lien he The Gioi Trai Cay.');
    }
}
