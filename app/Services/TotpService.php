<?php

namespace App\Services;

use Illuminate\Support\Str;

class TotpService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $bytes = 20): string
    {
        return $this->base32Encode(random_bytes($bytes));
    }

    public function verify(string $secret, string $code, int $window = 1): ?int
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($code) !== 6) {
            return null;
        }

        $currentStep = intdiv(time(), 30);
        for ($offset = -$window; $offset <= $window; $offset++) {
            $step = $currentStep + $offset;
            if (hash_equals($this->codeForStep($secret, $step), $code)) {
                return $step;
            }
        }

        return null;
    }

    public function provisioningUri(string $secret, string $email, string $issuer): string
    {
        $issuer = trim($issuer) ?: 'FruitShop';
        $label = rawurlencode($issuer.':'.$email);

        return 'otpauth://totp/'.$label.'?'.http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function recoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => strtoupper(Str::random(5).'-'.Str::random(5)))
            ->all();
    }

    public function normalizeRecoveryCode(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
    }

    private function codeForStep(string $secret, int $step): string
    {
        $binarySecret = $this->base32Decode($secret);
        $counter = pack('N2', 0, $step);
        $hash = hash_hmac('sha1', $counter, $binarySecret, true);
        $offset = ord($hash[19]) & 0x0f;
        $value = (
            ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff)
        ) % 1_000_000;

        return str_pad((string) $value, 6, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $data): string
    {
        $bits = '';
        foreach (str_split($data) as $character) {
            $bits .= str_pad(decbin(ord($character)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($bits, 5) as $chunk) {
            $encoded .= self::ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $encoded;
    }

    private function base32Decode(string $encoded): string
    {
        $encoded = strtoupper(preg_replace('/[^A-Z2-7]/', '', $encoded) ?? '');
        $bits = '';
        foreach (str_split($encoded) as $character) {
            $position = strpos(self::ALPHABET, $character);
            if ($position === false) {
                continue;
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $decoded = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $decoded .= chr(bindec($chunk));
            }
        }

        return $decoded;
    }
}
