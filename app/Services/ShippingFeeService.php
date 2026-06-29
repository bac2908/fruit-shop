<?php

namespace App\Services;

class ShippingFeeService
{
    const DELIVERY_LOCAL_EXPRESS = 'local_express';
    const DELIVERY_PROVINCE_PARTNER = 'province_partner';
    const DELIVERY_CONTACT_REQUIRED = 'contact_required';

    const FEE_STATUS_CONFIRMED = 'confirmed';
    const FEE_STATUS_ESTIMATED = 'estimated';
    const FEE_STATUS_PENDING_ADDRESS = 'pending_address';

    public function quote(int $subtotal, ?string $provinceCode, ?string $wardName = null): array
    {
        $provinceCode = trim((string) $provinceCode);

        if ($provinceCode === '') {
            return [
                'fee' => 0,
                'base_fee' => 0,
                'zone_key' => null,
                'zone_name' => 'Chưa chọn địa chỉ',
                'delivery_method' => null,
                'delivery_method_label' => 'Chưa chọn địa chỉ',
                'eta' => null,
                'fee_status' => self::FEE_STATUS_PENDING_ADDRESS,
                'fee_status_label' => 'Chưa tính phí',
                'requires_confirmation' => true,
                'is_free' => false,
                'is_pending' => true,
                'message' => 'Phí vận chuyển sẽ được tính sau khi chọn Tỉnh/Thành.',
            ];
        }

        $rate = $this->rateForProvince($provinceCode);
        $baseFee = (int) ($rate['fee'] ?? config('shop.shipping.default_fee', 70000));
        $remoteSurcharge = $this->remoteSurcharge($wardName);
        $baseFee += $remoteSurcharge;

        $freeThreshold = $this->freeThreshold();
        $isLocalExpress = $this->isLocalExpressProvince($provinceCode) && $remoteSurcharge === 0;
        $isFree = $isLocalExpress && $freeThreshold > 0 && $subtotal >= $freeThreshold;
        $deliveryMethod = $this->deliveryMethodFor($isLocalExpress, $remoteSurcharge);
        $requiresConfirmation = $deliveryMethod !== self::DELIVERY_LOCAL_EXPRESS;
        $feeStatus = $requiresConfirmation ? self::FEE_STATUS_ESTIMATED : self::FEE_STATUS_CONFIRMED;

        return [
            'fee' => $isFree ? 0 : $baseFee,
            'base_fee' => $baseFee,
            'zone_key' => $rate['key'] ?? 'other',
            'zone_name' => $rate['label'] ?? 'Khu vực toàn quốc',
            'delivery_method' => $deliveryMethod,
            'delivery_method_label' => $this->deliveryMethodLabels()[$deliveryMethod],
            'eta' => $this->etaFor($deliveryMethod),
            'fee_status' => $feeStatus,
            'fee_status_label' => $this->feeStatusLabels()[$feeStatus],
            'requires_confirmation' => $requiresConfirmation,
            'is_free' => $isFree,
            'is_pending' => false,
            'message' => $this->messageFor($deliveryMethod, $rate['label'] ?? 'Khu vực toàn quốc', $remoteSurcharge, $isFree),
        ];
    }

    public function freeThreshold(): int
    {
        return (int) config('shop.shipping.free_threshold', 500000);
    }

    public function frontendRules(): array
    {
        $rules = [];

        foreach ((array) config('shop.shipping.zones', []) as $key => $zone) {
            foreach ((array) ($zone['province_codes'] ?? []) as $provinceCode) {
                $rules[(string) $provinceCode] = [
                    'fee' => (int) ($zone['fee'] ?? config('shop.shipping.default_fee', 70000)),
                    'zone_key' => (string) $key,
                    'zone_name' => (string) ($zone['label'] ?? 'Khu vực toàn quốc'),
                ];
            }
        }

        return [
            'free_threshold' => $this->freeThreshold(),
            'default_fee' => (int) config('shop.shipping.default_fee', 70000),
            'default_zone_name' => 'Khu vực toàn quốc',
            'local_express_province_code' => (string) config('shop.shipping.local_express_province_code', '79'),
            'local_express_eta' => (string) config('shop.shipping.local_express_eta', '30 - 90 phút'),
            'province_partner_eta' => (string) config('shop.shipping.province_partner_eta', '2 - 48 giờ làm việc'),
            'remote_ward_surcharge' => (int) config('shop.shipping.remote_ward_surcharge', 20000),
            'remote_keywords' => array_values((array) config('shop.shipping.remote_keywords', [])),
            'province_rates' => $rules,
        ];
    }

    public function deliveryMethodLabels(): array
    {
        return [
            self::DELIVERY_LOCAL_EXPRESS => 'Giao nhanh nội vùng',
            self::DELIVERY_PROVINCE_PARTNER => 'Gửi tỉnh qua đối tác',
            self::DELIVERY_CONTACT_REQUIRED => 'Shop xác nhận riêng',
        ];
    }

    public function feeStatusLabels(): array
    {
        return [
            self::FEE_STATUS_CONFIRMED => 'Phí đã chốt',
            self::FEE_STATUS_ESTIMATED => 'Phí tạm tính',
            self::FEE_STATUS_PENDING_ADDRESS => 'Chưa tính phí',
        ];
    }

    private function rateForProvince(string $provinceCode): array
    {
        foreach ((array) config('shop.shipping.zones', []) as $key => $zone) {
            if (in_array($provinceCode, array_map('strval', (array) ($zone['province_codes'] ?? [])), true)) {
                return [
                    'key' => (string) $key,
                    'label' => (string) ($zone['label'] ?? 'Khu vực toàn quốc'),
                    'fee' => (int) ($zone['fee'] ?? config('shop.shipping.default_fee', 70000)),
                ];
            }
        }

        return [
            'key' => 'other',
            'label' => 'Khu vực toàn quốc',
            'fee' => (int) config('shop.shipping.default_fee', 70000),
        ];
    }

    private function remoteSurcharge(?string $wardName): int
    {
        $wardName = trim((string) $wardName);

        if ($wardName === '') {
            return 0;
        }

        foreach ((array) config('shop.shipping.remote_keywords', []) as $keyword) {
            if ($keyword !== '' && mb_stripos($wardName, (string) $keyword) !== false) {
                return (int) config('shop.shipping.remote_ward_surcharge', 20000);
            }
        }

        return 0;
    }

    private function isLocalExpressProvince(string $provinceCode): bool
    {
        return $provinceCode === (string) config('shop.shipping.local_express_province_code', '79');
    }

    private function deliveryMethodFor(bool $isLocalExpress, int $remoteSurcharge): string
    {
        if ($isLocalExpress) {
            return self::DELIVERY_LOCAL_EXPRESS;
        }

        if ($remoteSurcharge > 0) {
            return self::DELIVERY_CONTACT_REQUIRED;
        }

        return self::DELIVERY_PROVINCE_PARTNER;
    }

    private function etaFor(string $deliveryMethod): string
    {
        if ($deliveryMethod === self::DELIVERY_LOCAL_EXPRESS) {
            return (string) config('shop.shipping.local_express_eta', '30 - 90 phút');
        }

        if ($deliveryMethod === self::DELIVERY_CONTACT_REQUIRED) {
            return (string) config('shop.shipping.contact_required_eta', 'Shop liên hệ xác nhận');
        }

        return (string) config('shop.shipping.province_partner_eta', '2 - 48 giờ làm việc');
    }

    private function messageFor(string $deliveryMethod, string $zoneName, int $remoteSurcharge, bool $isFree): string
    {
        if ($deliveryMethod === self::DELIVERY_LOCAL_EXPRESS) {
            return $isFree
                ? 'Giao nhanh 30 - 90 phút tại TP.HCM. Miễn phí vận chuyển cho đơn từ ' . number_format($this->freeThreshold(), 0, ',', '.') . '₫.'
                : 'Giao nhanh 30 - 90 phút tại TP.HCM. Phí đã chốt theo khu vực.';
        }

        if ($deliveryMethod === self::DELIVERY_CONTACT_REQUIRED) {
            return $zoneName . ' + phụ phí khu vực đặc biệt. Shop sẽ xác nhận lại khả năng giao hàng và phí cuối cùng trước khi xử lý.';
        }

        return $zoneName . '. Phí đang là tạm tính cho đơn hàng tỉnh; shop sẽ xác nhận đóng gói, thời gian và phí cuối cùng trước khi giao.';
    }
}
