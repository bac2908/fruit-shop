<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'unit',
        'stock',
        'low_stock_threshold',
        'price',
        'sale_price',
        'cost_price',
        'thumb',
        'short_desc',
        'description',
        'is_active',
        'has_gear_detail',
        'sort_order',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'integer',
        'sale_price' => 'integer',
        'cost_price' => 'integer',
        'stock' => 'integer',
        'low_stock_threshold' => 'integer',
        'has_gear_detail' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /**
     * Get the actual price (sale_price if available, otherwise price)
     */
    public function getActualPriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }

    /**
     * Get discount amount
     */
    public function getDiscountAmountAttribute()
    {
        return $this->sale_price ? $this->price - $this->sale_price : 0;
    }

    /**
     * Get discount percentage
     */
    public function getDiscountPercentAttribute()
    {
        if (!$this->sale_price || $this->price == 0) {
            return 0;
        }
        return round(($this->price - $this->sale_price) / $this->price * 100);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeOrderable($query)
    {
        return $query->active()
            ->inStock()
            ->where(function ($q) {
                $q->where('price', '>', 0)
                    ->orWhere('sale_price', '>', 0);
            });
    }

    public function scopeOnSale($query)
    {
        return $query->whereNotNull('sale_price');
    }

    public function getThumbUrlAttribute(): string
    {
        $thumb = trim((string) ($this->thumb ?? ''));

        if ($thumb === '') {
            return '//theme.hstatic.net/200000157781/1001036201/14/no-image.jpg?v=1064';
        }

        return $this->resolveMediaUrl($thumb);
    }

    public function getPrimaryImageUrlAttribute(): string
    {
        $imageUrl = '';

        if ($this->relationLoaded('images')) {
            $imageUrl = trim((string) optional($this->images->first())->url);
        } else {
            $imageUrl = trim((string) $this->images()->value('url'));
        }

        if ($imageUrl !== '') {
            return $this->resolveMediaUrl($imageUrl);
        }

        return $this->thumb_url;
    }

    private function resolveMediaUrl(string $rawPath): string
    {
        return MediaUrl::resolve($rawPath);
    }
}
