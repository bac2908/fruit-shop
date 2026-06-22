<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'code',
        'type',
        'value',
        'min_order_total',
        'starts_at',
        'ends_at',
        'is_active',
        'usage_limit',
        'per_customer_limit',
        'used_count',
        'max_discount',
        'description',
        'image_url',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'value' => 'integer',
        'min_order_total' => 'integer',
        'usage_limit' => 'integer',
        'per_customer_limit' => 'integer',
        'used_count' => 'integer',
        'max_discount' => 'integer',
    ];

    // Coupon types
    const TYPE_PERCENT = 'percent';
    const TYPE_FIXED = 'fixed';
    const TYPE_GIFT = 'gift';

    /**
     * Check if coupon is valid (active and within date range)
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();

        if ($this->starts_at && $now->lessThan($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->greaterThan($this->ends_at)) {
            return false;
        }

        if ($this->usage_limit && (int) $this->used_count >= (int) $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Check if coupon has expired
     */
    public function isExpired(): bool
    {
        return $this->ends_at && Carbon::now()->greaterThan($this->ends_at);
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount($subtotal): int
    {
        if (!$this->isValid()) {
            return 0;
        }

        if ($this->min_order_total && $subtotal < $this->min_order_total) {
            return 0;
        }

        if ($this->type === self::TYPE_PERCENT) {
            $discount = intval($subtotal * $this->value / 100);
        } elseif ($this->type === self::TYPE_FIXED) {
            $discount = min($this->value, $subtotal);
        } else {
            return 0;
        }

        if ($this->max_discount) {
            $discount = min($discount, (int) $this->max_discount);
        }

        return $discount;
    }

    /**
     * Get the coupon images
     */
    public function images()
    {
        return $this->hasMany(CouponImage::class)->orderBy('sort_order');
    }

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Get primary image URL
     */
    public function getImageUrlAttribute()
    {
        $image = $this->images()->first();
        return $image ? $image->url : null;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        $now = Carbon::now();
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', $now);
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<', Carbon::now());
    }
}
