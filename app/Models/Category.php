<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'slogan',
        'icon_url',
        'meta_title',
        'meta_description',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get all parent categories (including self)
     */
    public function ancestors()
    {
        $ancestors = collect([$this]);
        $parent = $this->parent;

        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }

        return $ancestors;
    }

    /**
     * Get all descendant categories
     */
    public function descendants()
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }

        return $descendants;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get icon URL based on category slug
     * Presentation logic moved from view to model
     */
    public function getIconUrl(): string
    {
        if (trim((string) $this->icon_url) !== '') {
            return (string) $this->icon_url;
        }

        $slug = $this->slug;

        $iconMapping = [
            'nhap-khau' => 'https://file.hstatic.net/200000157781/file/icon_trai_cay-02_98cd992c58a04c5b833e519ecd829da5.png',
            'thai-lan' => 'https://file.hstatic.net/200000157781/file/icon_trai_cay-03_1f6c43580e534b128b5c70c201835617.png',
            'gio-qua-set-qua' => 'https://file.hstatic.net/200000157781/file/icon_trai_cay-04_cadc55b66acf46ecab5d0679333de4da.png',
            'combo' => 'https://file.hstatic.net/200000157781/file/icon_trai_cay-06_aeb5fa4462504ed28ae248032b1e092f.png',
            'vao-mua' => 'https://file.hstatic.net/200000157781/file/icon_trai_cay-09_19acc6d55acf4ecbaa0f3df934643e16.png',
            'ban-chay' => 'https://file.hstatic.net/200000157781/file/icon_trai_cay-08_30de1301ca884641b38adbf94506cf60.png',
            'tet-at-ty' => 'https://file.hstatic.net/200000157781/file/icon_trai_cay-07_21f5737708654ce69c12645338d01013.png',
        ];

        // Check for exact matches first
        foreach ($iconMapping as $key => $icon) {
            if ($slug === $key) {
                return $icon;
            }
        }

        // Check for partial matches
        if (str_contains($slug, 'nhap-khau')) {
            return $iconMapping['nhap-khau'];
        }
        if (str_contains($slug, 'thai-lan')) {
            return $iconMapping['thai-lan'];
        }
        if (str_contains($slug, 'gio-qua') && str_contains($slug, 'set-qua')) {
            return $iconMapping['gio-qua-set-qua'];
        }
        if (str_contains($slug, 'qua-cuoi') || str_contains($slug, 'mam-cung')) {
            return 'https://file.hstatic.net/200000157781/file/icon_trai_cay-05_73f0679afec848c6bff1b124c2b92581.png';
        }
        if (str_contains($slug, 'combo')) {
            return $iconMapping['combo'];
        }
        if (str_contains($slug, 'vao-mua')) {
            return $iconMapping['vao-mua'];
        }
        if (str_contains($slug, 'ban-chay')) {
            return $iconMapping['ban-chay'];
        }
        if (str_contains($slug, 'tet-at-ty')) {
            return $iconMapping['tet-at-ty'];
        }

        // Default icon
        return 'https://file.hstatic.net/200000157781/file/icon_trai_cay-01_11c3a33683d047ada039913fa5e7af97.png';
    }
}
