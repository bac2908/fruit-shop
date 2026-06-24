<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserVoucher extends Model
{
    protected $fillable = [
        'user_id',
        'coupon_id',
        'assigned_at',
        'used_at',
        'expires_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function getIsUsableAttribute(): bool
    {
        if ($this->used_at) {
            return false;
        }

        if (!$this->coupon || !$this->coupon->isValid()) {
            return false;
        }

        $expiresAt = $this->effective_expires_at;

        return !$expiresAt || now()->lessThanOrEqualTo($expiresAt);
    }

    public function getEffectiveExpiresAtAttribute()
    {
        return collect([
            $this->expires_at,
            optional($this->coupon)->ends_at,
        ])->filter()->sortBy(function ($date) {
            return $date->timestamp;
        })->first();
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->used_at) {
            return 'Đã dùng';
        }

        $expiresAt = $this->effective_expires_at;

        if ($expiresAt && now()->greaterThan($expiresAt)) {
            return 'Hết hạn';
        }

        if (!$this->coupon || !$this->coupon->isValid()) {
            return 'Tạm ngưng';
        }

        return 'Dùng được';
    }

    public function getStatusToneAttribute(): string
    {
        if ($this->is_usable) {
            return 'success';
        }

        return $this->used_at ? 'muted' : 'danger';
    }

    public function getExpiryLabelAttribute(): string
    {
        $expiresAt = $this->effective_expires_at;

        if ($expiresAt) {
            return 'Hết hạn ' . $expiresAt->format('d/m/Y H:i');
        }

        return 'Không giới hạn thời gian';
    }
}
