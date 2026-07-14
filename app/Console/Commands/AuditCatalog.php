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

        $this->info('Catalog products audited: ' . $products->count());
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
            $this->info('Report written to: ' . $path);
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
        if (!$product->category) {
            $this->issue($issues, $product, 'missing_category', 'critical', 'Product has no valid category.');
        }

        if ((int) $product->price <= 0) {
            $this->issue($issues, $product, 'invalid_price', 'critical', 'Base price must be greater than zero.');
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
        foreach ($mediaPaths as $mediaPath) {
            $mediaPath = trim((string) $mediaPath);
            if (Str::startsWith($mediaPath, ['http://', 'https://', '//'])) {
                $this->issue($issues, $product, 'external_media', 'warning', 'Media is hosted by a third party and should be migrated to owned storage.');
                break;
            }

            $relativePath = ltrim((string) parse_url($mediaPath, PHP_URL_PATH), '/');
            if ($relativePath !== '' && !File::exists(public_path($relativePath))) {
                $this->issue($issues, $product, 'missing_local_media', 'critical', 'Referenced local media file does not exist: ' . $relativePath);
                break;
            }
        }

        if ((int) $product->stock <= 0 && $product->is_active) {
            $this->issue($issues, $product, 'active_out_of_stock', 'warning', 'Active product is out of stock; keep visible only when restocking is intentional.');
        }

        if (trim((string) $product->meta_title) === '') {
            if ($fix) {
                $product->forceFill(['meta_title' => Str::limit($product->name . ' | Thế Giới Trái Cây', 60, '')])->save();
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
        $base = 'TGC-' . str_pad((string) $product->id, 6, '0', STR_PAD_LEFT);
        $candidate = $base;
        $suffix = 1;

        while (Product::query()->where('sku', $candidate)->whereKeyNot($product->id)->exists()) {
            $candidate = $base . '-' . $suffix++;
        }

        return $candidate;
    }

    private function metaDescription(Product $product): string
    {
        $source = trim(strip_tags((string) ($product->short_desc ?: $product->description)));
        $source = preg_replace('/\s+/u', ' ', $source);

        return Str::limit($source, 155, '');
    }

    private function reportPath(string $relativePath): string
    {
        $root = realpath(base_path());
        $target = base_path(ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR));
        $normalized = dirname($target) . DIRECTORY_SEPARATOR . basename($target);

        if (!$root || !Str::startsWith(strtolower($normalized), strtolower($root . DIRECTORY_SEPARATOR))) {
            throw new RuntimeException('The report path must stay inside the project directory.');
        }

        return $normalized;
    }
}
