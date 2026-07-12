<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\User;
use App\Models\UserVoucher;
use Illuminate\Support\Collection;

class VoucherSelectionService
{
    public function optionsFor(User $user, int $subtotal): Collection
    {
        $options = UserVoucher::query()
            ->with('coupon')
            ->where('user_id', $user->id)
            ->get()
            ->filter(function (UserVoucher $voucher) {
                return (bool) $voucher->coupon;
            })
            ->map(function (UserVoucher $voucher) use ($user, $subtotal) {
                $coupon = $voucher->coupon;
                $used = (bool) $voucher->used_at || $coupon->hasBeenUsedBy($user->id, $user->email);
                $reason = $used
                    ? 'Voucher đã được tài khoản của bạn sử dụng.'
                    : $coupon->getInvalidReason($subtotal, $user->id, $user->email);
                $missingAmount = $coupon->min_order_total
                    ? max(0, (int) $coupon->min_order_total - $subtotal)
                    : 0;

                if (!$reason && !$voucher->is_usable) {
                    $reason = $voucher->status_label;
                }

                return [
                    'voucher' => $voucher,
                    'coupon' => $coupon,
                    'eligible' => $reason === null && $missingAmount === 0,
                    'used' => $used,
                    'reason' => $missingAmount > 0
                        ? 'Mua thêm ' . number_format($missingAmount, 0, ',', '.') . 'đ để sử dụng.'
                        : $reason,
                    'missing_amount' => $missingAmount,
                    'estimated_value' => $coupon->benefitValueFor($subtotal),
                    'auto_priority' => $coupon->type === Coupon::TYPE_GIFT ? 1 : 0,
                    'recommended' => false,
                ];
            })
            ->sortBy(function (array $option) {
                return [
                    $option['eligible'] ? 0 : 1,
                    (int) $option['auto_priority'],
                    -1 * (int) $option['estimated_value'],
                    (int) $option['coupon']->min_order_total,
                ];
            })
            ->values();

        $bestIndex = $options->search(function (array $option) {
            return $option['eligible'];
        });

        if ($bestIndex !== false) {
            $best = $options->get($bestIndex);
            $best['recommended'] = true;
            $options->put($bestIndex, $best);
        }

        return $options;
    }

    public function bestEligible(Collection $options): ?Coupon
    {
        $best = $options->first(function (array $option) {
            return $option['eligible'];
        });

        return $best['coupon'] ?? null;
    }
}
