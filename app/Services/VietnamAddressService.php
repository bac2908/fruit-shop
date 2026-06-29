<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class VietnamAddressService
{
    private $data;

    public function all(): array
    {
        if ($this->data !== null) {
            return $this->data;
        }

        $path = public_path('data/vietnam-addresses.json');

        if (!is_file($path)) {
            return $this->data = [];
        }

        $decoded = json_decode(file_get_contents($path), true);

        return $this->data = is_array($decoded) ? $decoded : [];
    }

    public function provincesForSelect(): array
    {
        return collect($this->all())
            ->map(function (array $province) {
                return [
                    'code' => (string) ($province['Code'] ?? ''),
                    'name' => (string) ($province['FullName'] ?? ''),
                ];
            })
            ->filter(function (array $province) {
                return $province['code'] !== '' && $province['name'] !== '';
            })
            ->values()
            ->all();
    }

    public function resolve(string $provinceCode, string $wardCode): array
    {
        $provinceCode = trim($provinceCode);
        $wardCode = trim($wardCode);
        $province = $this->findProvince($provinceCode);

        if (!$province) {
            throw ValidationException::withMessages([
                'province_code' => 'Tỉnh/Thành không hợp lệ. Vui lòng chọn lại từ danh sách.',
            ]);
        }

        $ward = collect($province['Wards'] ?? [])
            ->first(function (array $ward) use ($wardCode, $provinceCode) {
                return (string) ($ward['Code'] ?? '') === $wardCode
                    && (string) ($ward['ProvinceCode'] ?? '') === $provinceCode;
            });

        if (!$ward) {
            throw ValidationException::withMessages([
                'ward_code' => 'Phường/Xã không thuộc Tỉnh/Thành đã chọn. Vui lòng chọn lại.',
            ]);
        }

        return [
            'province_code' => $provinceCode,
            'province_name' => (string) $province['FullName'],
            'ward_code' => $wardCode,
            'ward_name' => (string) $ward['FullName'],
        ];
    }

    private function findProvince(string $provinceCode): ?array
    {
        return collect($this->all())
            ->first(function (array $province) use ($provinceCode) {
                return (string) ($province['Code'] ?? '') === $provinceCode;
            });
    }
}
