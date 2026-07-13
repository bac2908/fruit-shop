<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Throwable;

final class LocalDateTime
{
    public static function timezone(): string
    {
        return (string) config('app.display_timezone', 'Asia/Ho_Chi_Minh');
    }

    public static function now(): Carbon
    {
        return Carbon::now(self::timezone());
    }

    public static function format($value, string $format = 'd/m/Y H:i', string $fallback = '-'): string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        try {
            if ($value instanceof CarbonInterface) {
                $date = $value->copy();
            } elseif ($value instanceof DateTimeInterface) {
                $date = Carbon::instance($value);
            } else {
                $date = Carbon::parse($value, (string) config('app.timezone', 'UTC'));
            }

            return $date->setTimezone(self::timezone())->format($format);
        } catch (Throwable $exception) {
            report($exception);

            return $fallback;
        }
    }

    public static function fromLocalInput($value): ?Carbon
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return Carbon::parse($value, self::timezone())->utc();
    }
}
