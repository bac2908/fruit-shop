<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_READ = 'read';
    public const STATUS_REPLIED = 'replied';
    public const STATUS_SPAM = 'spam';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'name',
        'user_id',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'handled_by',
        'admin_note',
        'reply_message',
        'read_at',
        'replied_at',
        'fingerprint',
        'spam_score',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
        'spam_score' => 'integer',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_NEW,
            self::STATUS_READ,
            self::STATUS_REPLIED,
            self::STATUS_SPAM,
            self::STATUS_ARCHIVED,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
