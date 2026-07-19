<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class AuditCatalog extends Command
{
    /** @var array<int, array{count:int, median:float}> */
    private array $categoryPriceStats = [];

    protected $signature = 'catalog:audit
        {--fix-safe : Apply deterministic fixes that do not change business pricing or stock}
        {--product= : Limit the audit to one product ID}
        {--json= : Write the complete report to a JSON file inside the project}
        {--strict : Return a failing exit code when unresolved critical issues remain}';

    protected $description = 'Audit catalog quality and optionally apply deterministic safe fixes.';

    public function handle(): int
    {
        $query = Product::query()->with(['category', 'images']);

        if ($this->option('product')) {
            $query->whereKey((int) $this->option('product'));
        }

        $products = $query->orderBy('id')->get();
        $this->categoryPriceStats = $products
            ->filter(fn (Product $product) => $product->category_id && (int) $product->price > 0)
            ->groupBy('category_id')
            ->map(fn ($items) => [
                'count' => $items->count(),
                'median' => (float) $items->median('price'),
            ])
            ->all();
        $issues = [];

        if ($this->option('fix-safe')) {
            DB::transaction(function () use ($products, &$issues) {
                foreach ($products as $product) {
                    $this->auditProduct($product, $issues, true);
                }
            });
        } else {
            foreach ($products as $product) {
                $this->auditProduct($product, $issues, false);
            }
        }

        $summary = collect($issues)
            ->groupBy('code')
            ->map(fn ($items) => [
                'total' => $items->count(),
                'fixed' => $items->where('fixed', true)->count(),
                'remaining' => $items->where('fixed', false)->count(),
            ])
            ->sortKeys()
            ->all();

        $this->info('Catalog products audited: '.$products->count());
        $this->table(
            ['Code', 'Total', 'Fixed', 'Remaining'],
            collect($summary)->map(fn ($row, $code) => [$code, $row['total'], $row['fixed'], $row['remaining']])->values()->all()
        );

        if ($issues === []) {
            $this->info('No catalog quality issues found.');
        }

        if ($this->option('json')) {
            $path = $this->reportPath((string) $this->option('json'));
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode([
                'generated_at' => now()->toIso8601String(),
                'products_audited' => $products->count(),
                'safe_fix_enabled' => (bool) $this->option('fix-safe'),
                'summary' => $summary,
                'issues' => $issues,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->info('Report written to: '.$path);
        }

        $unresolvedCritical = collect($issues)
            ->where('severity', 'critical')
            ->where('fixed', false)
            ->count();

        return $this->option('strict') && $unresolvedCritical > 0
            ? Command::FAILURE
            : Command::SUCCESS;
    }

    private function auditProduct(Product $product, array &$issues, bool $fix): void
    {
        if (! $product->category) {
            $this->issue($issues, $product, 'missing_category', 'critical', 'Product has no valid category.');
        }

        if ((int) $product->price <= 0) {
            $this->issue($issues, $product, 'invalid_price', 'critical', 'Base price must be greater than zero.');
        }

        $categoryPrice = $this->categoryPriceStats[(int) $product->category_id] ?? null;
        if (
            $categoryPrice
            && $categoryPrice['count'] >= 8
            && $categoryPrice['median'] > 0
            && ((int) $product->price > $categoryPrice['median'] * 4 || (int) $product->price < $categoryPrice['median'] / 4)
        ) {
            $this->issue(
                $issues,
                $product,
                'price_outlier',
                'warning',
                'Price differs by more than 4x from the category median and needs a manual market review.'
            );
        }

        if ($product->sale_price !== null && ((int) $product->sale_price <= 0 || (int) $product->sale_price >= (int) $product->price)) {
            if ($fix) {
                $product->forceFill(['sale_price' => null])->save();
            }
            $this->issue($issues, $product, 'invalid_sale_price', 'warning', 'Sale price must be positive and lower than base price.', $fix);
        }

        if (trim((string) $product->sku) === '') {
            if ($fix) {
                $product->forceFill(['sku' => $this->uniqueSku($product)])->save();
            }
            $this->issue($issues, $product, 'missing_sku', 'warning', 'Product needs a stable inventory SKU.', $fix);
        }

        $trimmedName = preg_replace('/\s+/u', ' ', trim((string) $product->name));
        if ($trimmedName !== $product->name) {
            if ($fix) {
                $product->forceFill(['name' => $trimmedName])->save();
            }
            $this->issue($issues, $product, 'name_whitespace', 'warning', 'Product name contains redundant whitespace.', $fix);
        }

        if (trim((string) $product->unit) === '') {
            $inferredUnit = $this->inferUnit($product);
            $fixed = $fix && $inferredUnit !== null;

            if ($fixed) {
                $product->forceFill(['unit' => $inferredUnit])->save();
                $product->unit = $inferredUnit;
            }

            $this->issue(
                $issues,
                $product,
                'missing_unit',
                'warning',
                $inferredUnit === null
                    ? 'Product selling unit is missing and cannot be inferred safely.'
                    : "Product selling unit is missing; inferred as {$inferredUnit} from its name.",
                $fixed
            );
        }

        if ((int) $product->stock < 0) {
            $this->issue($issues, $product, 'negative_stock', 'critical', 'Stock cannot be negative.');
        }

        if ((int) $product->low_stock_threshold < 0) {
            $this->issue($issues, $product, 'negative_low_stock_threshold', 'warning', 'Low-stock threshold cannot be negative.');
        }

        $plainDescription = $this->plainText((string) $product->description);
        if ($plainDescription === '') {
            $this->issue($issues, $product, 'missing_description', 'critical', 'Product description is missing.');
        } elseif (Str::length($plainDescription) < 80) {
            $this->issue($issues, $product, 'short_description_content', 'warning', 'Product description has fewer than 80 characters.');
        }

        $plainSummary = $this->plainText((string) $product->short_desc);
        if ($plainSummary === '') {
            $this->issue($issues, $product, 'missing_short_description', 'warning', 'Product summary is missing.');
        } elseif (Str::length($plainSummary) < 40) {
            $this->issue($issues, $product, 'short_summary_content', 'warning', 'Product summary has fewer than 40 characters.');
        }

        $localMediaPath = $this->localOptimizedMediaPath($product);
        $primaryMediaPath = trim((string) optional($product->images->first())->url) ?: trim((string) $product->thumb);

        if (Str::startsWith($primaryMediaPath, ['http://', 'https://', '//'])) {
            $fixed = false;

            if ($fix && $localMediaPath !== null) {
                $product->forceFill(['thumb' => $localMediaPath])->save();

                $primaryImage = $product->images->first();
                if ($primaryImage) {
                    $primaryImage->forceFill(['url' => $localMediaPath])->save();
                    $primaryImage->url = $localMediaPath;
                }

                $product->thumb = $localMediaPath;
                $fixed = true;
            }

            $this->issue(
                $issues,
                $product,
                'external_primary_media',
                $localMediaPath === null ? 'critical' : 'warning',
                $localMediaPath === null
                    ? 'Primary media is external and no owned local mirror was found.'
                    : 'Primary media is external while an optimized local mirror is available.',
                $fixed
            );
        }

        if ($product->images->isEmpty()) {
            $thumb = trim((string) $product->thumb);
            $fixed = false;

            if ($fix && $thumb !== '') {
                $product->images()->firstOrCreate(['url' => $thumb], ['sort_order' => 0]);
                $fixed = true;
            }

            $this->issue($issues, $product, 'missing_image_relation', $thumb === '' ? 'critical' : 'warning', 'Product image gallery is empty.', $fixed);
        }

        $mediaPaths = $product->images->pluck('url')->push($product->thumb)->filter()->unique();
        $hasExternalGalleryMedia = false;
        foreach ($mediaPaths as $mediaPath) {
            $mediaPath = trim((string) $mediaPath);
            if (Str::startsWith($mediaPath, ['http://', 'https://', '//'])) {
                $hasExternalGalleryMedia = true;

                continue;
            }

            $relativePath = ltrim((string) parse_url($mediaPath, PHP_URL_PATH), '/');
            if ($relativePath !== '' && ! File::exists(public_path($relativePath))) {
                $this->issue($issues, $product, 'missing_local_media', 'critical', 'Referenced local media file does not exist: '.$relativePath);
                break;
            }
        }

        if ($hasExternalGalleryMedia) {
            $this->issue($issues, $product, 'external_gallery_media', 'warning', 'One or more gallery images are still hosted by a third party.');
        }

        if ((int) $product->stock <= 0 && $product->is_active) {
            $this->issue($issues, $product, 'active_out_of_stock', 'warning', 'Active product is out of stock; keep visible only when restocking is intentional.');
        }

        if (trim((string) $product->meta_title) === '') {
            if ($fix) {
                $product->forceFill(['meta_title' => Str::limit($product->name.' | Thế Giới Trái Cây', 60, '')])->save();
            }
            $this->issue($issues, $product, 'missing_meta_title', 'warning', 'SEO title is missing.', $fix);
        }

        if (trim((string) $product->meta_description) === '') {
            $description = $this->metaDescription($product);
            $fixed = $fix && $description !== '';
            if ($fixed) {
                $product->forceFill(['meta_description' => $description])->save();
            }
            $this->issue($issues, $product, 'missing_meta_description', 'warning', 'SEO description is missing.', $fixed);
        }
    }

    private function issue(array &$issues, Product $product, string $code, string $severity, string $message, bool $fixed = false): void
    {
        $issues[] = [
            'product_id' => (int) $product->id,
            'sku' => $product->sku,
            'slug' => $product->slug,
            'code' => $code,
            'severity' => $severity,
            'message' => $message,
            'fixed' => $fixed,
        ];
    }

    private function uniqueSku(Product $product): string
    {
        $base = 'TGC-'.str_pad((string) $product->id, 6, '0', STR_PAD_LEFT);
        $candidate = $base;
        $suffix = 1;

        while (Product::query()->where('sku', $candidate)->whereKeyNot($product->id)->exists()) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }

    private function metaDescription(Product $product): string
    {
        $source = trim(strip_tags((string) ($product->short_desc ?: $product->description)));
        $source = preg_replace('/\s+/u', ' ', $source);

        return Str::limit($source, 155, '');
    }

    private function plainText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function localOptimizedMediaPath(Product $product): ?string
    {
        $base = 'images/products_synced/'.$product->id.'-'.$product->slug;

        foreach (['webp', 'jpg', 'jpeg', 'png'] as $extension) {
            $relativePath = $base.'.'.$extension;

            if (File::exists(public_path($relativePath))) {
                return $relativePath;
            }

            $matches = File::glob(public_path('images/products_synced/'.$product->id.'-*.'.$extension));
            if (count($matches) === 1) {
                return 'images/products_synced/'.basename($matches[0]);
            }
        }

        return null;
    }

    private function inferUnit(Product $product): ?string
    {
        $name = Str::lower($this->plainText((string) $product->name));

        if (preg_match('/(^|\s)mâm(\s|$)/u', $name) === 1) {
            return 'mâm';
        }

        if (preg_match('/(^|[\s-])phần(\s|$)/u', $name) === 1) {
            return 'phần';
        }

        if (preg_match('/^(quýt|na\s|táo\s|cam\s|đào\s|lựu\s|bơ\s|dưa\s|lê\s|kiwi\s|xoài\s|đu đủ\s)/u', $name) === 1) {
            return 'kg';
        }

        return null;
    }

    private function reportPath(string $relativePath): string
    {
        $root = realpath(base_path());
        $target = base_path(ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR));
        $normalized = dirname($target).DIRECTORY_SEPARATOR.basename($target);

        if (! $root || ! Str::startsWith(strtolower($normalized), strtolower($root.DIRECTORY_SEPARATOR))) {
            throw new RuntimeException('The report path must stay inside the project directory.');
        }

        return $normalized;
    }
}
