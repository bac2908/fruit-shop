<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerResetPasswordNotification extends Notification
{
    private $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Đặt lại mật khẩu Thế Giới Trái Cây')
            ->greeting('Xin chào ' . $notifiable->name . ',')
            ->line('Bạn nhận được email này vì có yêu cầu đặt lại mật khẩu cho tài khoản tại Thế Giới Trái Cây.')
            ->action('Đặt lại mật khẩu', $resetUrl)
            ->line('Liên kết này có hiệu lực trong ' . config('auth.passwords.users.expire') . ' phút.')
            ->line('Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này.');
    }
}
