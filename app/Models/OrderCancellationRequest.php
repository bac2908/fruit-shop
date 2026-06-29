<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderCancellationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'status',
        'reason',
        'note',
        'requested_at',
        'resolved_by',
        'resolved_at',
        'admin_note',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    const REASON_WRONG_ITEM = 'wrong_item';
    const REASON_CHANGE_ADDRESS = 'change_address';
    const REASON_NO_NEED = 'no_need';
    const REASON_BETTER_PRICE = 'better_price';
    const REASON_DUPLICATE = 'duplicate_order';
    const REASON_PAYMENT_ISSUE = 'payment_issue';
    const REASON_OTHER = 'other';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Chờ shop duyệt',
            self::STATUS_APPROVED => 'Đã duyệt hủy',
            self::STATUS_REJECTED => 'Shop từ chối hủy',
        ];
    }

    public static function reasonLabels(): array
    {
        return [
            self::REASON_WRONG_ITEM => 'Đặt nhầm sản phẩm',
            self::REASON_CHANGE_ADDRESS => 'Muốn đổi địa chỉ hoặc số điện thoại',
            self::REASON_NO_NEED => 'Không còn nhu cầu',
            self::REASON_BETTER_PRICE => 'Tìm được giá tốt hơn',
            self::REASON_DUPLICATE => 'Đặt trùng đơn',
            self::REASON_PAYMENT_ISSUE => 'Gặp vấn đề khi thanh toán',
            self::REASON_OTHER => 'Lý do khác',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? 'Chưa xác định';
    }

    public function getReasonLabelAttribute(): string
    {
        return self::reasonLabels()[$this->reason] ?? 'Lý do khác';
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
