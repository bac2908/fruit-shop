<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactReplyNotification extends Notification
{
    public function __construct(private ContactMessage $contactMessage, private string $reply)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Phản hồi từ Thế Giới Trái Cây')
            ->greeting('Xin chào ' . $this->contactMessage->name . ',')
            ->line('Cảm ơn bạn đã gửi yêu cầu đến Thế Giới Trái Cây.')
            ->line($this->reply)
            ->line('Nội dung bạn đã gửi: ' . $this->contactMessage->message)
            ->action('Quay lại website', route('home'));
    }
}
