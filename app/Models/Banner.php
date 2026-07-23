<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Banner extends Model
{
    protected $fillable = [
        'placement',
        'title',
        'subtitle',
        'alt_text',
        'image_url',
        'link_url',
        'sort_order',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function scopeVisible($query)
    {
        return $query
            ->where('is_active', true)
            ->where(function ($inner) {
                $inner->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($inner) {
                $inner->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function resolvedImageUrl(): string
    {
        $path = trim((string) $this->image_url);

        if (Str::startsWith($path, ['http://', 'https://', '//', '/'])) {
            return $path;
        }

        return asset($path);
    }

    public function resolvedLinkUrl(): string
    {
        $path = trim((string) $this->link_url);
        if ($path === '') {
            return '#';
        }

        if (Str::startsWith($path, ['http://', 'https://', '//', '/', '#'])) {
            return $path;
        }

        return url($path);
    }
}
