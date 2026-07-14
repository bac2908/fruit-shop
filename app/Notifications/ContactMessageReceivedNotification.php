<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactMessageReceivedNotification extends Notification
{
    public function __construct(private ContactMessage $contactMessage)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Liên hệ mới từ ' . $this->contactMessage->name)
            ->greeting('Website vừa nhận một yêu cầu tư vấn mới.')
            ->line('Khách hàng: ' . $this->contactMessage->name)
            ->line('Email: ' . $this->contactMessage->email)
            ->line('Điện thoại: ' . ($this->contactMessage->phone ?: 'Không cung cấp'))
            ->line('Nội dung: ' . $this->contactMessage->message)
            ->action('Mở hộp thư liên hệ', route('admin.contacts.show', $this->contactMessage));
    }
}
