<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use App\Models\UserVoucher;
use App\Support\LocalDateTime;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminCouponService
{
    private const IMMUTABLE_AFTER_USAGE = [
        'code',
        'type',
        'value',
        'gift_product_id',
        'gift_quantity',
        'min_order_total',
        'max_discount',
        'per_customer_limit',
        'is_public',
        'starts_at',
    ];

    public function __construct(
        private SecurityAuditService $audit,
        private CustomerNotificationService $notifications
    ) {}

    public function create(array $data, array $auditContext): Coupon
    {
        return DB::transaction(function () use ($data, $auditContext) {
            $coupon = new Coupon($this->payload($data));
            $this->ensureUsageLimit($coupon);
            $this->ensureActivatable($coupon);
            $coupon->save();

            $this->audit->record('admin_coupon_created', $auditContext, [
                'coupon_id' => $coupon->id,
                'code' => $coupon->code,
                'type' => $coupon->type,
                'is_public' => (bool) $coupon->is_public,
            ]);

            return $coupon->fresh(['giftProduct']);
        });
    }

    public function update(Coupon $coupon, array $data, array $auditContext): Coupon
    {
        return DB::transaction(function () use ($coupon, $data, $auditContext) {
            $coupon = Coupon::query()->whereKey($coupon->id)->lockForUpdate()->firstOrFail();
            $payload = $this->payload($data);
            $hasUsage = $this->hasUsage($coupon);

            if ($hasUsage
                && $coupon->type === Coupon::TYPE_GIFT
                && (int) $coupon->gift_product_id === (int) ($payload['gift_product_id'] ?? 0)) {
                $payload['value'] = (int) $coupon->value;
            }

            if ($hasUsage) {
                $changedProtectedFields = collect(self::IMMUTABLE_AFTER_USAGE)
                    ->filter(fn (string $field) => $this->changed($coupon, $payload, $field))
                    ->values();

                if ($changedProtectedFields->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'coupon' => 'Voucher đã được sử dụng nên không thể đổi mã, loại, giá trị, điều kiện hoặc phạm vi áp dụng.',
                    ]);
                }
            }

            $before = Arr::only($coupon->getAttributes(), array_keys($payload));
            $coupon->forceFill($payload)->unsetRelation('giftProduct');
            $this->ensureUsageLimit($coupon);
            $this->ensureActivatable($coupon);
            $coupon->save();

            $this->audit->record('admin_coupon_updated', $auditContext, [
                'coupon_id' => $coupon->id,
                'code' => $coupon->code,
                'changes' => $this->changes($before, $coupon),
            ]);

            return $coupon->fresh(['giftProduct']);
        });
    }

    public function toggle(Coupon $coupon, array $auditContext): Coupon
    {
        return DB::transaction(function () use ($coupon, $auditContext) {
            $coupon = Coupon::query()->whereKey($coupon->id)->lockForUpdate()->firstOrFail();
            $nextState = ! $coupon->is_active;
            $coupon->forceFill(['is_active' => $nextState])->unsetRelation('giftProduct');
            $this->ensureActivatable($coupon);
            $coupon->save();

            $this->audit->record('admin_coupon_status_changed', $auditContext, [
                'coupon_id' => $coupon->id,
                'code' => $coupon->code,
                'is_active' => $nextState,
            ]);

            return $coupon;
        });
    }

    public function archive(Coupon $coupon, array $auditContext): void
    {
        DB::transaction(function () use ($coupon, $auditContext) {
            $coupon = Coupon::query()->whereKey($coupon->id)->lockForUpdate()->firstOrFail();
            $coupon->forceFill(['is_active' => false])->save();
            $coupon->delete();

            $this->audit->record('admin_coupon_archived', $auditContext, [
                'coupon_id' => $coupon->id,
                'code' => $coupon->code,
                'used_count' => (int) $coupon->used_count,
            ]);
        });
    }

    public function restore(int $couponId, array $auditContext): Coupon
    {
        return DB::transaction(function () use ($couponId, $auditContext) {
            $coupon = Coupon::withTrashed()->whereKey($couponId)->lockForUpdate()->firstOrFail();

            if ($coupon->trashed()) {
                $coupon->restore();
            }

            $coupon->forceFill(['is_active' => false])->save();
            $this->audit->record('admin_coupon_restored', $auditContext, [
                'coupon_id' => $coupon->id,
                'code' => $coupon->code,
            ]);

            return $coupon;
        });
    }

    public function assign(array $data, array $auditContext): array
    {
        return DB::transaction(function () use ($data, $auditContext) {
            $coupon = Coupon::query()
                ->with('giftProduct')
                ->whereKey((int) $data['coupon_id'])
                ->lockForUpdate()
                ->firstOrFail();
            $this->ensureAssignable($coupon, $data['expires_at'] ?? null);

            $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];
            $users = $data['target'] === 'all_customers'
                ? User::query()->where('role', 'customer')->orderBy('id')->get()
                : User::query()->where('role', 'customer')->where('email', $data['email'])->get();

            foreach ($users as $user) {
                $status = $this->assignToUser($coupon, $user, $data['expires_at'] ?? null);
                $result[$status]++;
            }

            $this->audit->record('admin_coupon_assigned', $auditContext, [
                'coupon_id' => $coupon->id,
                'code' => $coupon->code,
                'target' => $data['target'],
                'email' => $data['target'] === 'single' ? $data['email'] : null,
                'result' => $result,
            ]);

            return array_merge($result, ['coupon' => $coupon]);
        });
    }

    private function assignToUser(Coupon $coupon, User $user, ?string $expiresAt): string
    {
        if ($coupon->per_customer_limit
            && $coupon->usageCountFor($user->id, $user->email) >= (int) $coupon->per_customer_limit) {
            return 'skipped';
        }

        $voucher = UserVoucher::query()->firstOrCreate(
            ['user_id' => $user->id, 'coupon_id' => $coupon->id],
            [
                'assigned_at' => now(),
                'used_at' => null,
                'expires_at' => LocalDateTime::fromLocalInput($expiresAt),
            ]
        );

        if ($voucher->wasRecentlyCreated) {
            $this->notifications->voucherReceived($user, $coupon, $voucher);

            return 'created';
        }

        if ($voucher->used_at) {
            return 'skipped';
        }

        $newExpiry = LocalDateTime::fromLocalInput($expiresAt);
        if (($voucher->expires_at?->timestamp) !== ($newExpiry?->timestamp)) {
            $voucher->forceFill(['expires_at' => $newExpiry])->save();

            return 'updated';
        }

        return 'skipped';
    }

    private function payload(array $data): array
    {
        $type = $data['type'];
        $giftProductId = $type === Coupon::TYPE_GIFT ? (int) $data['gift_product_id'] : null;
        $giftQuantity = $type === Coupon::TYPE_GIFT ? (int) $data['gift_quantity'] : 1;
        $value = (int) ($data['value'] ?? 0);

        if ($giftProductId) {
            $product = Product::query()->whereKey($giftProductId)->firstOrFail();
            $value = $product->orderable_price * $giftQuantity;
        }

        return [
            'title' => $data['title'],
            'code' => $data['code'],
            'type' => $type,
            'gift_product_id' => $giftProductId,
            'gift_quantity' => $giftQuantity,
            'value' => $value,
            'min_order_total' => (int) ($data['min_order_total'] ?? 0),
            'max_discount' => $type === Coupon::TYPE_PERCENT ? ($data['max_discount'] ?? null) : null,
            'usage_limit' => $data['usage_limit'] ?? null,
            'per_customer_limit' => $data['per_customer_limit'] ?? null,
            'starts_at' => LocalDateTime::fromLocalInput($data['starts_at'] ?? null),
            'ends_at' => LocalDateTime::fromLocalInput($data['ends_at'] ?? null),
            'is_active' => (bool) $data['is_active'],
            'is_public' => (bool) $data['is_public'],
            'description' => $data['description'] ?? null,
        ];
    }

    private function hasUsage(Coupon $coupon): bool
    {
        return (int) $coupon->used_count > 0 || $coupon->usages()->exists();
    }

    private function ensureUsageLimit(Coupon $coupon): void
    {
        if ($coupon->usage_limit !== null && (int) $coupon->usage_limit < (int) $coupon->used_count) {
            throw ValidationException::withMessages([
                'usage_limit' => 'Tổng lượt dùng không được nhỏ hơn '.number_format((int) $coupon->used_count).' lượt đã phát sinh.',
            ]);
        }
    }

    private function ensureActivatable(Coupon $coupon): void
    {
        if (! $coupon->is_active) {
            return;
        }

        if ($coupon->ends_at && now()->greaterThan($coupon->ends_at)) {
            throw ValidationException::withMessages([
                'ends_at' => 'Không thể bật voucher đã hết hạn. Hãy gia hạn trước.',
            ]);
        }

        if ($coupon->usage_limit && (int) $coupon->used_count >= (int) $coupon->usage_limit) {
            throw ValidationException::withMessages([
                'usage_limit' => 'Không thể bật voucher đã hết lượt. Hãy tăng giới hạn sử dụng trước.',
            ]);
        }

        if ($error = $coupon->giftInventoryError()) {
            throw ValidationException::withMessages(['gift_product_id' => $error]);
        }
    }

    private function ensureAssignable(Coupon $coupon, ?string $expiresAt): void
    {
        if (! $coupon->is_active || $coupon->isExpired()) {
            throw ValidationException::withMessages([
                'coupon_id' => 'Chỉ có thể phát voucher đang bật và chưa hết hạn.',
            ]);
        }

        if ($coupon->usage_limit && (int) $coupon->used_count >= (int) $coupon->usage_limit) {
            throw ValidationException::withMessages(['coupon_id' => 'Voucher đã hết lượt sử dụng.']);
        }

        if ($error = $coupon->giftInventoryError()) {
            throw ValidationException::withMessages(['coupon_id' => $error]);
        }

        $assignmentExpiry = LocalDateTime::fromLocalInput($expiresAt);
        if ($assignmentExpiry && $coupon->ends_at && $assignmentExpiry->greaterThan($coupon->ends_at)) {
            throw ValidationException::withMessages([
                'expires_at' => 'Hạn riêng không được sau hạn chung của voucher.',
            ]);
        }
    }

    private function changed(Coupon $coupon, array $payload, string $field): bool
    {
        $current = $coupon->getAttribute($field);
        $next = $payload[$field] ?? null;

        if ($current instanceof \DateTimeInterface || $next instanceof \DateTimeInterface) {
            $currentMinute = $current ? $current->format('Y-m-d H:i') : null;
            $nextMinute = $next ? $next->format('Y-m-d H:i') : null;

            return $currentMinute !== $nextMinute;
        }

        return (string) $current !== (string) $next;
    }

    private function changes(array $before, Coupon $coupon): array
    {
        $changes = [];

        foreach ($before as $field => $oldValue) {
            $newValue = $coupon->getAttribute($field);
            $oldComparable = $oldValue instanceof \DateTimeInterface ? $oldValue->getTimestamp() : (string) $oldValue;
            $newComparable = $newValue instanceof \DateTimeInterface ? $newValue->getTimestamp() : (string) $newValue;

            if ($oldComparable !== $newComparable) {
                $changes[$field] = ['from' => $oldValue, 'to' => $newValue];
            }
        }

        return $changes;
    }
}
