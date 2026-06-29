<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class LowStockProductsNotification extends Notification
{
    private $products;

    public function __construct(Collection $products)
    {
        $this->products = $products;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Canh bao ton kho thap - The Gioi Trai Cay')
            ->greeting('Xin chao admin,')
            ->line('He thong phat hien cac san pham dang co ton kho thap. Hay kiem tra va nhap hang kip thoi.');

        foreach ($this->products->take(10) as $product) {
            $threshold = (int) ($product->low_stock_threshold ?: config('shop.order_automation.low_stock_fallback_threshold', 5));
            $mail->line('- ' . $product->name . ': con ' . (int) $product->stock . ', nguong canh bao ' . $threshold . '.');
        }

        if ($this->products->count() > 10) {
            $mail->line('Va ' . ($this->products->count() - 10) . ' san pham khac can kiem tra.');
        }

        return $mail
            ->action('Mo trang san pham admin', route('admin.products'))
            ->line('Day la email tu dong tu he thong quan ly ton kho.');
    }
}
