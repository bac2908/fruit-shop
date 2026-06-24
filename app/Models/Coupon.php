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
        'is_public',
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
        'is_public' => 'boolean',
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

    public function getInvalidReason(int $subtotal = 0, ?int $userId = null, ?string $email = null): ?string
    {
        if (!$this->is_active) {
            return 'Voucher này đang tạm ngưng.';
        }

        $now = Carbon::now();

        if ($this->starts_at && $now->lessThan($this->starts_at)) {
            return 'Voucher này chưa đến thời gian sử dụng.';
        }

        if ($this->ends_at && $now->greaterThan($this->ends_at)) {
            return 'Voucher này đã hết hạn.';
        }

        if ($this->usage_limit && (int) $this->used_count >= (int) $this->usage_limit) {
            return 'Voucher này đã hết lượt sử dụng.';
        }

        if (!$this->is_public && !$this->hasUsableVoucherFor($userId)) {
            return 'Voucher này chỉ dành cho tài khoản được gán riêng.';
        }

        if ($this->min_order_total && $subtotal > 0 && $subtotal < (int) $this->min_order_total) {
            return 'Đơn hàng cần tối thiểu ' . number_format((int) $this->min_order_total, 0, ',', '.') . 'đ để dùng voucher này.';
        }

        if ($this->per_customer_limit && $this->usageCountFor($userId, $email) >= (int) $this->per_customer_limit) {
            return 'Bạn đã dùng voucher này đủ số lần cho phép.';
        }

        return null;
    }

    public function usageCountFor(?int $userId = null, ?string $email = null): int
    {
        $email = $email ? strtolower(trim($email)) : null;

        if (!$userId && !$email) {
            return 0;
        }

        return $this->usages()
            ->where(function ($query) use ($userId, $email) {
                if ($userId) {
                    $query->where('user_id', $userId);
                }

                if ($email) {
                    if ($userId) {
                        $query->orWhereRaw('LOWER(customer_email) = ?', [$email]);
                    } else {
                        $query->whereRaw('LOWER(customer_email) = ?', [$email]);
                    }
                }
            })
            ->count();
    }

    public function hasUsableVoucherFor(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        return $this->userVouchers()
            ->where('user_id', $userId)
            ->whereNull('used_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', Carbon::now());
            })
            ->exists();
    }

    public function getDiscountLabelAttribute(): string
    {
        if ($this->type === self::TYPE_PERCENT) {
            $label = 'Giảm ' . (int) $this->value . '%';

            if ($this->max_discount) {
                $label .= ', tối đa ' . number_format((int) $this->max_discount, 0, ',', '.') . 'đ';
            }

            return $label;
        }

        if ($this->type === self::TYPE_FIXED) {
            return 'Giảm ' . number_format((int) $this->value, 0, ',', '.') . 'đ';
        }

        return 'Quà tặng';
    }

    public function getConditionLabelAttribute(): string
    {
        if ($this->min_order_total) {
            return 'Đơn từ ' . number_format((int) $this->min_order_total, 0, ',', '.') . 'đ';
        }

        return 'Không yêu cầu giá trị đơn tối thiểu';
    }

    public function getExpiryLabelAttribute(): string
    {
        if ($this->ends_at) {
            return 'Hết hạn ' . $this->ends_at->format('d/m/Y H:i');
        }

        return 'Không giới hạn thời gian';
    }

    public function getUsageLabelAttribute(): string
    {
        if ($this->usage_limit) {
            $remaining = max(0, (int) $this->usage_limit - (int) $this->used_count);
            return 'Còn ' . number_format($remaining, 0, ',', '.') . ' lượt';
        }

        return 'Không giới hạn lượt dùng';
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

    public function userVouchers()
    {
        return $this->hasMany(UserVoucher::class);
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
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhere('usage_limit', '<=', 0)
                    ->orWhereColumn('used_count', '<', 'usage_limit');
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<', Carbon::now());
    }
}
