<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'public_token',
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'shipping_address',
        'customer_note',
        'admin_note',
        'subtotal',
        'shipping_fee',
        'discount_total',
        'total',
        'coupon_code',
        'status',
        'payment_method',
        'payment_status',
        'paid_at',
        'shipping_status',
        'shipping_provider',
        'tracking_code',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'shipping_fee' => 'integer',
        'discount_total' => 'integer',
        'total' => 'integer',
        'status' => 'string',
        'payment_status' => 'string',
        'shipping_status' => 'string',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Order statuses
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_SHIPPING = 'shipping';
    const STATUS_DONE = 'done';
    const STATUS_CANCELLED = 'cancelled';

    const PAYMENT_STATUS_UNPAID = 'unpaid';
    const PAYMENT_STATUS_PAID = 'paid';
    const PAYMENT_STATUS_REFUNDED = 'refunded';

    const PAYMENT_METHOD_COD = 'cod';
    const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';
    const PAYMENT_METHOD_MOMO = 'momo';

    const SHIPPING_STATUS_PENDING = 'pending';
    const SHIPPING_STATUS_PREPARING = 'preparing';
    const SHIPPING_STATUS_SHIPPING = 'shipping';
    const SHIPPING_STATUS_DELIVERED = 'delivered';
    const SHIPPING_STATUS_FAILED = 'failed';

    public static function paymentMethodLabels(): array
    {
        return [
            self::PAYMENT_METHOD_COD => 'Thanh toán khi nhận hàng',
            self::PAYMENT_METHOD_BANK_TRANSFER => 'Chuyển khoản ngân hàng',
            self::PAYMENT_METHOD_MOMO => 'Ví MoMo sandbox',
        ];
    }

    public static function paymentStatusLabels(): array
    {
        return [
            self::PAYMENT_STATUS_UNPAID => 'Chưa thanh toán',
            self::PAYMENT_STATUS_PAID => 'Đã thanh toán',
            self::PAYMENT_STATUS_REFUNDED => 'Đã hoàn tiền',
        ];
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::paymentMethodLabels()[$this->payment_method] ?? 'Chưa xác định';
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return self::paymentStatusLabels()[$this->payment_status] ?? 'Chưa xác định';
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeShipping($query)
    {
        return $query->where('status', self::STATUS_SHIPPING);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_SHIPPING]);
    }
}
