<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderReturnRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'status',
        'type',
        'reason',
        'note',
        'evidence_path',
        'refund_method',
        'refund_account',
        'refund_amount',
        'requested_at',
        'resolved_by',
        'resolved_at',
        'refunded_at',
        'admin_note',
    ];

    protected $casts = [
        'refund_amount' => 'integer',
        'requested_at' => 'datetime',
        'resolved_at' => 'datetime',
        'refunded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_REFUNDED = 'refunded';

    const STATUS_COMPLETED = 'completed';

    const TYPE_EXCHANGE = 'exchange';

    const TYPE_REFUND = 'refund';

    const REFUND_METHOD_BANK = 'bank_transfer';

    const REFUND_METHOD_MOMO = 'momo';

    const REFUND_METHOD_CONTACT = 'contact';

    const REASON_DAMAGED = 'damaged';

    const REASON_WRONG_ITEM = 'wrong_item';

    const REASON_MISSING_ITEM = 'missing_item';

    const REASON_SPOILED = 'spoiled';

    const REASON_QUALITY = 'quality_issue';

    const REASON_OTHER = 'other';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Chờ shop kiểm tra',
            self::STATUS_APPROVED => 'Đã duyệt xử lý',
            self::STATUS_REJECTED => 'Shop từ chối',
            self::STATUS_REFUNDED => 'Đã hoàn tiền',
            self::STATUS_COMPLETED => 'Đã đổi sản phẩm',
        ];
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_EXCHANGE => 'Đổi sản phẩm',
            self::TYPE_REFUND => 'Hoàn tiền',
        ];
    }

    public static function reasonLabels(): array
    {
        return [
            self::REASON_DAMAGED => 'Sản phẩm dập/hư hỏng khi nhận',
            self::REASON_WRONG_ITEM => 'Giao sai sản phẩm',
            self::REASON_MISSING_ITEM => 'Thiếu sản phẩm hoặc sai số lượng',
            self::REASON_SPOILED => 'Sản phẩm có dấu hiệu hư/spoil sớm',
            self::REASON_QUALITY => 'Chất lượng không đúng mô tả',
            self::REASON_OTHER => 'Lý do khác',
        ];
    }

    public static function refundMethodLabels(): array
    {
        return [
            self::REFUND_METHOD_BANK => 'Chuyển khoản ngân hàng',
            self::REFUND_METHOD_MOMO => 'Ví MoMo',
            self::REFUND_METHOD_CONTACT => 'Shop liên hệ để xác nhận',
        ];
    }

    public function getRefundMethodLabelAttribute(): string
    {
        return self::refundMethodLabels()[$this->refund_method] ?? 'Chưa chọn';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? 'Chưa xác định';
    }

    public function getTypeLabelAttribute(): string
    {
        return self::typeLabels()[$this->type] ?? 'Yêu cầu hỗ trợ';
    }

    public function getReasonLabelAttribute(): string
    {
        return self::reasonLabels()[$this->reason] ?? 'Lý do khác';
    }

    public function getEvidenceUrlAttribute(): ?string
    {
        if (! $this->evidence_path) {
            return null;
        }

        return asset('storage/'.ltrim($this->evidence_path, '/'));
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
