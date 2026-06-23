<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'recipient_name',
        'phone',
        'address_line',
        'ward',
        'district',
        'province',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address_line,
            $this->ward,
            $this->district,
            $this->province,
        ])->filter()->implode(', ');
    }
}
