<?php

namespace App\Support;

use Illuminate\Support\Str;

class MediaUrl
{
    public static function resolve(string $rawPath): string
    {
        $rawPath = trim($rawPath);

        if ($rawPath === '') {
            return '';
        }

        if (Str::startsWith($rawPath, ['http://', 'https://', '//'])) {
            return $rawPath;
        }

        $path = ltrim($rawPath, '/');
        $rootUrl = rtrim((string) url('/'), '/');

        // If app is accessed via server.php, static files must be served from /public.
        if (Str::endsWith(Str::lower($rootUrl), '/server.php')) {
            $base = preg_replace('#/server\.php$#i', '', $rootUrl) ?: $rootUrl;

            if (!Str::endsWith(Str::lower($base), '/public')) {
                $base .= '/public';
            }

            return rtrim($base, '/') . '/' . $path;
        }

        return asset($path);
    }
}
