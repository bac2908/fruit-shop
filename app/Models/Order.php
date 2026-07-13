<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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
        'shipping_province_code',
        'shipping_ward_code',
        'customer_note',
        'admin_note',
        'subtotal',
        'shipping_fee',
        'shipping_fee_status',
        'discount_total',
        'total',
        'coupon_code',
        'status',
        'payment_method',
        'payment_status',
        'momo_request_id',
        'momo_transaction_id',
        'payment_expires_at',
        'paid_at',
        'shipping_status',
        'shipping_delivery_method',
        'shipping_delivery_eta',
        'shipping_delivery_note',
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
        'payment_expires_at' => 'datetime',
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

    const SHIPPING_FEE_STATUS_CONFIRMED = 'confirmed';
    const SHIPPING_FEE_STATUS_ESTIMATED = 'estimated';
    const SHIPPING_FEE_STATUS_PENDING_ADDRESS = 'pending_address';

    const DELIVERY_METHOD_LOCAL_EXPRESS = 'local_express';
    const DELIVERY_METHOD_PROVINCE_PARTNER = 'province_partner';
    const DELIVERY_METHOD_CONTACT_REQUIRED = 'contact_required';

    protected static function booted()
    {
        static::updating(function (Order $order) {
            $wasPaid = $order->getOriginal('payment_status') === self::PAYMENT_STATUS_PAID;
            $moneyFields = ['subtotal', 'shipping_fee', 'discount_total', 'total'];

            if ($wasPaid && $order->isDirty($moneyFields)) {
                throw new \LogicException('Không thể thay đổi giá trị đơn hàng sau khi đã thanh toán.');
            }
        });
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Chờ shop xác nhận',
            self::STATUS_CONFIRMED => 'Đã xác nhận',
            self::STATUS_SHIPPING => 'Đang giao hàng',
            self::STATUS_DONE => 'Hoàn tất',
            self::STATUS_CANCELLED => 'Đã hủy',
        ];
    }

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

    public static function shippingFeeStatusLabels(): array
    {
        return [
            self::SHIPPING_FEE_STATUS_CONFIRMED => 'Phí đã chốt',
            self::SHIPPING_FEE_STATUS_ESTIMATED => 'Phí tạm tính',
            self::SHIPPING_FEE_STATUS_PENDING_ADDRESS => 'Chưa tính phí',
        ];
    }

    public static function deliveryMethodLabels(): array
    {
        return [
            self::DELIVERY_METHOD_LOCAL_EXPRESS => 'Giao nhanh nội vùng',
            self::DELIVERY_METHOD_PROVINCE_PARTNER => 'Gửi tỉnh qua đối tác',
            self::DELIVERY_METHOD_CONTACT_REQUIRED => 'Shop xác nhận riêng',
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

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? 'Chưa xác định';
    }

    public function getShippingFeeStatusLabelAttribute(): string
    {
        return self::shippingFeeStatusLabels()[$this->shipping_fee_status] ?? 'Chưa xác định';
    }

    public function getDeliveryMethodLabelAttribute(): string
    {
        return self::deliveryMethodLabels()[$this->shipping_delivery_method] ?? 'Chưa xác định';
    }

    public function requiresShippingConfirmation(): bool
    {
        return $this->shipping_fee_status !== self::SHIPPING_FEE_STATUS_CONFIRMED;
    }

    public function isCustomerCancellable(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->shipping_status === self::SHIPPING_STATUS_PENDING
            && $this->payment_status !== self::PAYMENT_STATUS_PAID;
    }

    public function isCustomerCancellationRequestable(): bool
    {
        if (in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_DONE], true)) {
            return false;
        }

        if ($this->shipping_status === self::SHIPPING_STATUS_SHIPPING || $this->shipping_status === self::SHIPPING_STATUS_DELIVERED) {
            return false;
        }

        if ($this->hasPendingCancellationRequest()) {
            return false;
        }

        return ($this->status === self::STATUS_PENDING && $this->payment_status === self::PAYMENT_STATUS_PAID)
            || $this->status === self::STATUS_CONFIRMED;
    }

    public function hasPendingCancellationRequest(): bool
    {
        if ($this->relationLoaded('cancellationRequests')) {
            return $this->cancellationRequests
                ->contains('status', OrderCancellationRequest::STATUS_PENDING);
        }

        return $this->cancellationRequests()
            ->where('status', OrderCancellationRequest::STATUS_PENDING)
            ->exists();
    }

    public function isReturnRequestable(): bool
    {
        if ($this->status !== self::STATUS_DONE || $this->shipping_status !== self::SHIPPING_STATUS_DELIVERED) {
            return false;
        }

        if ($this->hasPendingReturnRequest()) {
            return false;
        }

        $completedAt = $this->completedAtForReturnPolicy();
        if (!$completedAt) {
            return false;
        }

        return now()->lessThanOrEqualTo($completedAt->copy()->addHours((int) config('shop.returns.request_window_hours', 24)));
    }

    public function hasPendingReturnRequest(): bool
    {
        if ($this->relationLoaded('returnRequests')) {
            return $this->returnRequests
                ->contains('status', OrderReturnRequest::STATUS_PENDING);
        }

        return $this->returnRequests()
            ->where('status', OrderReturnRequest::STATUS_PENDING)
            ->exists();
    }

    public function getLatestReturnRequestAttribute(): ?OrderReturnRequest
    {
        if ($this->relationLoaded('returnRequests')) {
            return $this->returnRequests
                ->sortByDesc(function ($request) {
                    return optional($request->requested_at)->timestamp ?? 0;
                })
                ->first();
        }

        return $this->returnRequests()
            ->latest('requested_at')
            ->first();
    }

    public function returnRequestDeadline()
    {
        $completedAt = $this->completedAtForReturnPolicy();

        return $completedAt
            ? $completedAt->copy()->addHours((int) config('shop.returns.request_window_hours', 24))
            : null;
    }

    private function completedAtForReturnPolicy()
    {
        if ($this->relationLoaded('statusHistories')) {
            $history = $this->statusHistories
                ->where('status', self::STATUS_DONE)
                ->sortByDesc('created_at')
                ->first();

            if ($history) {
                return $history->created_at;
            }
        }

        $history = $this->statusHistories()
            ->where('status', self::STATUS_DONE)
            ->latest('created_at')
            ->first();

        return optional($history)->created_at ?: $this->updated_at;
    }

    public function getLatestCancellationRequestAttribute(): ?OrderCancellationRequest
    {
        if ($this->relationLoaded('cancellationRequests')) {
            return $this->cancellationRequests
                ->sortByDesc(function ($request) {
                    return optional($request->requested_at)->timestamp ?? 0;
                })
                ->first();
        }

        return $this->cancellationRequests()
            ->latest('requested_at')
            ->first();
    }

    public function trackingTimeline(): Collection
    {
        $histories = $this->relationLoaded('statusHistories') ? $this->statusHistories : collect();
        $historyTime = function (string $status) use ($histories) {
            return optional($histories->firstWhere('status', $status))->created_at;
        };

        $steps = collect([
            [
                'key' => 'placed',
                'label' => 'Đã đặt hàng',
                'description' => 'Hệ thống đã ghi nhận đơn hàng.',
                'time' => $this->created_at,
                'done' => true,
                'current' => false,
            ],
            [
                'key' => self::STATUS_PENDING,
                'label' => 'Chờ shop xác nhận',
                'description' => 'Shop kiểm tra tồn kho, địa chỉ và thanh toán.',
                'time' => $historyTime(self::STATUS_PENDING),
                'done' => in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_SHIPPING, self::STATUS_DONE], true),
                'current' => $this->status === self::STATUS_PENDING,
            ],
            [
                'key' => self::STATUS_CONFIRMED,
                'label' => 'Đã xác nhận',
                'description' => 'Đơn đã được shop xác nhận và chuẩn bị hàng.',
                'time' => $historyTime(self::STATUS_CONFIRMED),
                'done' => in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_SHIPPING, self::STATUS_DONE], true),
                'current' => $this->status === self::STATUS_CONFIRMED,
            ],
            [
                'key' => self::STATUS_SHIPPING,
                'label' => 'Đang giao hàng',
                'description' => 'Đơn đã rời shop và đang được giao.',
                'time' => $historyTime(self::STATUS_SHIPPING),
                'done' => in_array($this->status, [self::STATUS_SHIPPING, self::STATUS_DONE], true),
                'current' => $this->status === self::STATUS_SHIPPING,
            ],
            [
                'key' => self::STATUS_DONE,
                'label' => 'Hoàn tất',
                'description' => 'Khách đã nhận hàng thành công.',
                'time' => $historyTime(self::STATUS_DONE),
                'done' => $this->status === self::STATUS_DONE,
                'current' => $this->status === self::STATUS_DONE,
            ],
        ]);

        if ($this->status === self::STATUS_CANCELLED) {
            return collect([
                $steps->first(),
                [
                    'key' => self::STATUS_CANCELLED,
                    'label' => 'Đã hủy đơn',
                    'description' => 'Đơn đã được hủy và hoàn lại tồn kho nếu cần.',
                    'time' => $this->cancelled_at,
                    'done' => true,
                    'current' => true,
                ],
            ]);
        }

        $latestCancellationRequest = $this->latest_cancellation_request;
        if ($latestCancellationRequest && $latestCancellationRequest->status === OrderCancellationRequest::STATUS_PENDING) {
            $steps->push([
                'key' => 'cancel_requested',
                'label' => 'Đang chờ duyệt hủy',
                'description' => 'Shop sẽ kiểm tra yêu cầu hủy và phản hồi.',
                'time' => $latestCancellationRequest->requested_at,
                'done' => true,
                'current' => true,
            ]);
        }

        return $steps;
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

    public function cancellationRequests()
    {
        return $this->hasMany(OrderCancellationRequest::class);
    }

    public function returnRequests()
    {
        return $this->hasMany(OrderReturnRequest::class);
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
