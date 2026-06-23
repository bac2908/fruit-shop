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

        return !$this->expires_at || now()->lessThanOrEqualTo($this->expires_at);
    }
}
