<?php

namespace App\Notifications;

use App\Models\OrderReturnRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderReturnRequestNotification extends Notification
{
    private $returnRequest;
    private $event;

    public function __construct(OrderReturnRequest $returnRequest, string $event)
    {
        $this->returnRequest = $returnRequest;
        $this->event = $event;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $returnRequest = $this->returnRequest->loadMissing('order');
        $order = $returnRequest->order;
        $url = route('account.profile', ['tab' => 'orders']);
        $subject = 'Cap nhat yeu cau doi tra don ' . optional($order)->code;

        $mail = (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($subject)
            ->greeting('Xin chao ' . (optional($order)->customer_name ?: 'ban') . ',');

        if ($this->event === 'requested') {
            return $mail
                ->line('Shop da nhan yeu cau ' . $returnRequest->type_label . ' cho don ' . $order->code . '.')
                ->line('Ly do: ' . $returnRequest->reason_label . '.')
                ->line('Bo phan cham soc se kiem tra thong tin va phan hoi som.')
                ->action('Xem don hang', $url);
        }

        if ($this->event === 'approved') {
            $mail->line('Yeu cau ' . $returnRequest->type_label . ' cho don ' . $order->code . ' da duoc shop chap nhan.');

            if ($returnRequest->type === OrderReturnRequest::TYPE_REFUND) {
                $mail->line('So tien du kien hoan: ' . number_format((int) $returnRequest->refund_amount, 0, ',', '.') . 'd.');
                $mail->line('Shop se doi soat va hoan tien trong ' . config('shop.returns.refund_days', 3) . ' ngay lam viec.');
            } else {
                $mail->line('Shop se lien he de sap xep doi san pham phu hop.');
            }

            if ($returnRequest->admin_note) {
                $mail->line('Ghi chu tu shop: ' . $returnRequest->admin_note);
            }

            return $mail->action('Xem don hang', $url);
        }

        if ($this->event === 'rejected') {
            return $mail
                ->line('Yeu cau doi tra/hoan tien cho don ' . $order->code . ' chua du dieu kien xu ly.')
                ->line('Phan hoi tu shop: ' . ($returnRequest->admin_note ?: 'Vui long lien he shop de duoc ho tro them.'))
                ->action('Xem don hang', $url);
        }

        return $mail
            ->line('Shop da danh dau hoan tien cho don ' . $order->code . '.')
            ->line('So tien hoan: ' . number_format((int) $returnRequest->refund_amount, 0, ',', '.') . 'd.')
            ->line('Neu ngan hang hoac vi dien tu can them thoi gian doi soat, tien co the ve tai khoan cham hon mot chut.')
            ->action('Xem don hang', $url);
    }
}
