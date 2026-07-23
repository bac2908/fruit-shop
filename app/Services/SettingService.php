<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SettingService
{
    private const CACHE_KEY = 'shop_settings_v2';

    public function defaults(): array
    {
        return [
            'store_name' => ['value' => 'Thế Giới Trái Cây', 'type' => 'string', 'group' => 'store', 'public' => true],
            'store_hotline' => ['value' => '0333499426', 'type' => 'string', 'group' => 'store', 'public' => true],
            'store_email' => ['value' => (string) config('mail.from.address'), 'type' => 'string', 'group' => 'store', 'public' => true],
            'store_address' => ['value' => '74 Trần Thái Tông', 'type' => 'string', 'group' => 'store', 'public' => true],
            'display_timezone' => ['value' => (string) config('app.display_timezone', 'Asia/Ho_Chi_Minh'), 'type' => 'string', 'group' => 'store', 'public' => false],
            'payment_cod_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'payment', 'public' => true],
            'payment_bank_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'payment', 'public' => true],
            'payment_momo_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'payment', 'public' => true],
            'email_order_placed_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'notification', 'public' => false],
            'email_order_confirmed_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'notification', 'public' => false],
            'email_order_cancelled_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'notification', 'public' => false],
            'low_stock_alert_enabled' => ['value' => true, 'type' => 'boolean', 'group' => 'notification', 'public' => false],
            'low_stock_default_threshold' => ['value' => (int) config('shop.order_automation.low_stock_fallback_threshold', 5), 'type' => 'integer', 'group' => 'inventory', 'public' => false],
            'shipping_free_threshold' => ['value' => (int) config('shop.shipping.free_threshold', 500000), 'type' => 'integer', 'group' => 'shipping', 'public' => true],
            'shipping_default_fee' => ['value' => (int) config('shop.shipping.default_fee', 70000), 'type' => 'integer', 'group' => 'shipping', 'public' => true],
            'shipping_remote_surcharge' => ['value' => (int) config('shop.shipping.remote_ward_surcharge', 20000), 'type' => 'integer', 'group' => 'shipping', 'public' => true],
        ];
    }

    public function all(): array
    {
        $values = collect($this->defaults())->mapWithKeys(
            fn (array $definition, string $key) => [$key => $definition['value']]
        )->all();

        if (! Schema::hasTable('settings')) {
            return $values;
        }

        foreach ($this->stored() as $key => $setting) {
            $values[$key] = $this->cast($setting['value'], $setting['type']);
        }

        return $values;
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        return $this->all()[$key] ?? $fallback;
    }

    public function bool(string $key, bool $fallback = false): bool
    {
        return filter_var($this->get($key, $fallback), FILTER_VALIDATE_BOOLEAN);
    }

    public function int(string $key, int $fallback = 0): int
    {
        return (int) $this->get($key, $fallback);
    }

    public function putMany(array $values): void
    {
        $definitions = $this->defaults();

        foreach ($values as $key => $value) {
            if (! isset($definitions[$key])) {
                continue;
            }

            $definition = $definitions[$key];
            Setting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => $this->serialize($value, $definition['type']),
                    'type' => $definition['type'],
                    'group' => $definition['group'],
                    'is_public' => $definition['public'],
                ]
            );
        }

        Cache::forget(self::CACHE_KEY);
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function stored(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function () {
            return Setting::query()
                ->get(['key', 'value', 'type'])
                ->mapWithKeys(fn (Setting $setting) => [
                    $setting->key => [
                        'value' => $setting->value,
                        'type' => $setting->type,
                    ],
                ])
                ->all();
        });
    }

    private function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json' => json_decode((string) $value, true) ?: [],
            default => $value,
        };
    }

    private function serialize(mixed $value, string $type): ?string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) (int) $value,
            'json' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            default => $value === null ? null : trim((string) $value),
        };
    }
}
