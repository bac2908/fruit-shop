<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class CustomerVerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage())
            ->subject('Xác minh email tài khoản Thế Giới Trái Cây')
            ->greeting('Xin chào ' . ($notifiable->name ?: 'bạn') . ',')
            ->line('Hãy xác minh email để bảo vệ tài khoản và sử dụng đầy đủ chức năng mua hàng.')
            ->action('Xác minh email', $verificationUrl)
            ->line('Liên kết có hiệu lực trong ' . config('auth.verification.expire', 60) . ' phút.')
            ->line('Nếu bạn không tạo tài khoản này, bạn có thể bỏ qua email.');
    }
}
