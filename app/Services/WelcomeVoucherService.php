<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\User;
use App\Models\UserVoucher;

class WelcomeVoucherService
{
    private $notifications;

    public function __construct(CustomerNotificationService $notifications)
    {
        $this->notifications = $notifications;
    }

    public const CODES = [
        'GIOQUA10',
        'QUYTTHAI1KG',
        'KIWIVANG500',
    ];

    public function assignTo(User $user): int
    {
        if ($user->role !== 'customer') {
            return 0;
        }

        $coupons = Coupon::query()
            ->whereIn('code', self::CODES)
            ->get();

        $assigned = 0;

        foreach ($coupons as $coupon) {
            $latestUsage = CouponUsage::query()
                ->where('coupon_id', $coupon->id)
                ->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhereRaw('LOWER(customer_email) = ?', [strtolower($user->email)]);
                })
                ->latest('used_at')
                ->first();

            $voucher = UserVoucher::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'coupon_id' => $coupon->id,
                ],
                [
                    'assigned_at' => now(),
                    'used_at' => optional($latestUsage)->used_at,
                    'expires_at' => null,
                ]
            );

            if ($latestUsage && !$voucher->used_at) {
                $voucher->forceFill(['used_at' => $latestUsage->used_at])->save();
            }

            if ($voucher->wasRecentlyCreated) {
                $assigned++;
            }

            $this->notifications->voucherReceived($user, $coupon, $voucher);
        }

        return $assigned;
    }
}
