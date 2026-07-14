<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;

class ContactSpamGuard
{
    public const TOKEN_VALID = 'valid';
    public const TOKEN_TOO_FAST = 'too_fast';
    public const TOKEN_EXPIRED = 'expired';
    public const TOKEN_INVALID = 'invalid';

    public function issueToken(): string
    {
        return Crypt::encryptString(json_encode([
            'issued_at' => now()->timestamp,
            'nonce' => Str::random(24),
        ], JSON_THROW_ON_ERROR));
    }

    public function tokenState(?string $token): string
    {
        try {
            $payload = json_decode(Crypt::decryptString((string) $token), true, 512, JSON_THROW_ON_ERROR);
            $issuedAt = (int) ($payload['issued_at'] ?? 0);
        } catch (Throwable $exception) {
            return self::TOKEN_INVALID;
        }

        $age = now()->timestamp - $issuedAt;

        if ($age < (int) config('shop.contact.min_fill_seconds', 1)) {
            return self::TOKEN_TOO_FAST;
        }

        if ($age > ((int) config('shop.contact.form_expire_minutes', 240) * 60)) {
            return self::TOKEN_EXPIRED;
        }

        return self::TOKEN_VALID;
    }

    public function fingerprint(string $email, string $message): string
    {
        $normalized = Str::lower(trim($email)) . '|' . Str::lower(preg_replace('/\s+/u', ' ', trim($message)));

        return hash('sha256', $normalized);
    }

    public function score(string $name, string $email, string $message): int
    {
        $content = $name . ' ' . $email . ' ' . $message;
        $score = 0;
        $linkCount = preg_match_all('/(?:https?:\/\/|www\.)/iu', $content);

        if ($linkCount >= 3) {
            $score += 3;
        } elseif ($linkCount > 0) {
            $score += 1;
        }

        if (preg_match('/(.)\1{14,}/u', $content)) {
            $score += 2;
        }

        if (preg_match('/\b(?:casino|viagra|crypto giveaway|backlink|seo service)\b/iu', $content)) {
            $score += 3;
        }

        return min(10, $score);
    }
}
