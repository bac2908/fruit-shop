<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminProductService
{
    private const MAX_IMAGES = 8;

    public function __construct(private SecurityAuditService $audit) {}

    public function create(array $data, array $files, array $auditContext): Product
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($data, $files, $auditContext, &$storedPaths) {
                $payload = $this->productPayload($data);
                $payload['slug'] = $this->uniqueSlug($data['slug'] ?? '', $payload['name']);

                $product = Product::query()->create($payload);
                $uploadedUrls = $this->storeImages($files, $product->id, $storedPaths);

                foreach ($uploadedUrls as $index => $url) {
                    $product->images()->create([
                        'url' => $url,
                        'sort_order' => $index,
                    ]);
                }

                $this->ensurePublishable($product);
                $this->syncThumbnail($product);

                if ((int) $product->stock > 0) {
                    $this->recordStockMovement(
                        $product,
                        0,
                        (int) $product->stock,
                        $auditContext['user_id'] ?? null,
                        'initial_stock',
                        $data['stock_note'] ?? 'Tồn kho ban đầu khi tạo sản phẩm.'
                    );
                }

                $this->audit->record('admin_product_created', $auditContext, [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'stock' => (int) $product->stock,
                    'price' => (int) $product->price,
                    'images' => count($uploadedUrls),
                ]);

                return $product->fresh(['category', 'images']);
            });
        } catch (Throwable $exception) {
            $this->deleteStoredPaths($storedPaths);
            throw $exception;
        }
    }

    public function update(Product $product, array $data, array $files, array $auditContext): Product
    {
        $storedPaths = [];
        $pathsToDelete = [];

        try {
            $updated = DB::transaction(function () use ($product, $data, $files, $auditContext, &$storedPaths, &$pathsToDelete) {
                $product = Product::query()
                    ->with('images')
                    ->whereKey($product->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $before = Arr::only($product->getAttributes(), array_keys($this->productPayload($data)));
                $stockBefore = (int) $product->stock;
                $removeIds = collect($data['remove_images'] ?? [])->map(fn ($id) => (int) $id)->unique();
                $ownedRemoveIds = $product->images->whereIn('id', $removeIds)->pluck('id');
                $remainingCount = $product->images->whereNotIn('id', $ownedRemoveIds)->count();

                if ($remainingCount + count($files) > self::MAX_IMAGES) {
                    throw ValidationException::withMessages([
                        'images' => 'Sản phẩm chỉ được có tối đa '.self::MAX_IMAGES.' ảnh.',
                    ]);
                }

                $payload = $this->productPayload($data);
                $payload['slug'] = trim((string) ($data['slug'] ?? '')) !== ''
                    ? $data['slug']
                    : $product->slug;
                $product->forceFill($payload)->save();

                foreach ($product->images->whereIn('id', $ownedRemoveIds) as $image) {
                    $pathsToDelete[] = $this->localStoragePath($image->url);
                    $image->delete();
                }

                $orders = collect($data['existing_image_order'] ?? []);
                $product->images()->get()->each(function (ProductImage $image) use ($orders) {
                    if ($orders->has((string) $image->id) || $orders->has($image->id)) {
                        $image->forceFill([
                            'sort_order' => (int) ($orders[(string) $image->id] ?? $orders[$image->id]),
                        ])->save();
                    }
                });

                $nextSortOrder = (int) $product->images()->max('sort_order') + 1;
                foreach ($this->storeImages($files, $product->id, $storedPaths) as $url) {
                    $product->images()->create([
                        'url' => $url,
                        'sort_order' => $nextSortOrder++,
                    ]);
                }

                $this->ensurePublishable($product);
                $this->syncThumbnail($product);

                $stockAfter = (int) $product->stock;
                if ($stockAfter !== $stockBefore) {
                    $this->recordStockMovement(
                        $product,
                        $stockBefore,
                        $stockAfter,
                        $auditContext['user_id'] ?? null,
                        'admin_adjustment',
                        $data['stock_note'] ?? 'Admin điều chỉnh tồn kho trong trang sản phẩm.'
                    );
                }

                $changes = [];
                foreach ($before as $field => $oldValue) {
                    $newValue = $product->getAttribute($field);
                    if ((string) $oldValue !== (string) $newValue) {
                        $changes[$field] = ['from' => $oldValue, 'to' => $newValue];
                    }
                }

                $this->audit->record('admin_product_updated', $auditContext, [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'changes' => $changes,
                    'images_added' => count($files),
                    'images_removed' => $ownedRemoveIds->count(),
                ]);

                return $product->fresh(['category', 'images']);
            });

            $this->deleteStoredPaths(array_filter($pathsToDelete));

            return $updated;
        } catch (Throwable $exception) {
            $this->deleteStoredPaths($storedPaths);
            throw $exception;
        }
    }

    public function toggleVisibility(Product $product, array $auditContext): Product
    {
        return DB::transaction(function () use ($product, $auditContext) {
            $product = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $nextState = ! $product->is_active;

            if ($nextState) {
                $product->is_active = true;
                $this->ensurePublishable($product);
            }

            $product->forceFill(['is_active' => $nextState])->save();
            $this->audit->record('admin_product_visibility_changed', $auditContext, [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'is_active' => $nextState,
            ]);

            return $product;
        });
    }

    public function delete(Product $product, array $auditContext): void
    {
        DB::transaction(function () use ($product, $auditContext) {
            $product = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $product->forceFill(['is_active' => false])->save();
            $product->delete();

            $this->audit->record('admin_product_deleted', $auditContext, [
                'product_id' => $product->id,
                'sku' => $product->sku,
            ]);
        });
    }

    public function restore(int $productId, array $auditContext): Product
    {
        return DB::transaction(function () use ($productId, $auditContext) {
            $product = Product::withTrashed()->whereKey($productId)->lockForUpdate()->firstOrFail();

            if ($product->trashed()) {
                $product->restore();
            }

            $product->forceFill(['is_active' => false])->save();
            $this->audit->record('admin_product_restored', $auditContext, [
                'product_id' => $product->id,
                'sku' => $product->sku,
            ]);

            return $product;
        });
    }

    public function deleteImage(Product $product, ProductImage $image, array $auditContext): void
    {
        abort_unless((int) $image->product_id === (int) $product->id, 404);
        $pathToDelete = null;

        DB::transaction(function () use ($product, $image, $auditContext, &$pathToDelete) {
            $product = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $image = ProductImage::query()->whereKey($image->id)->lockForUpdate()->firstOrFail();

            if ($product->is_active && $product->images()->count() <= 1) {
                throw ValidationException::withMessages([
                    'images' => 'Sản phẩm đang hiển thị phải giữ lại ít nhất một ảnh.',
                ]);
            }

            $pathToDelete = $this->localStoragePath($image->url);
            $imageId = $image->id;
            $image->delete();
            $this->syncThumbnail($product);

            $this->audit->record('admin_product_image_deleted', $auditContext, [
                'product_id' => $product->id,
                'image_id' => $imageId,
            ]);
        });

        $this->deleteStoredPaths(array_filter([$pathToDelete]));
    }

    private function productPayload(array $data): array
    {
        $payload = Arr::only($data, [
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
            'short_desc',
            'description',
            'is_active',
            'has_gear_detail',
            'sort_order',
            'meta_title',
            'meta_description',
        ]);

        foreach (['sale_price', 'cost_price', 'category_id'] as $nullableInteger) {
            $payload[$nullableInteger] = ($payload[$nullableInteger] ?? '') === ''
                ? null
                : (int) $payload[$nullableInteger];
        }

        foreach (['stock', 'low_stock_threshold', 'price', 'sort_order'] as $integer) {
            $payload[$integer] = (int) ($payload[$integer] ?? 0);
        }

        foreach (['short_desc', 'meta_title', 'meta_description'] as $nullableText) {
            $payload[$nullableText] = $this->nullableText($payload[$nullableText] ?? null);
        }

        $payload['description'] = $this->sanitizeDescription($payload['description'] ?? null);
        $payload['is_active'] = (bool) ($payload['is_active'] ?? false);
        $payload['has_gear_detail'] = (bool) ($payload['has_gear_detail'] ?? false);

        return $payload;
    }

    private function uniqueSlug(string $requestedSlug, string $name): string
    {
        $base = Str::slug($requestedSlug !== '' ? $requestedSlug : $name) ?: 'san-pham';
        $slug = $base;
        $suffix = 2;

        while (Product::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function storeImages(array $files, int $productId, array &$storedPaths): array
    {
        return collect($files)
            ->filter(fn ($file) => $file instanceof UploadedFile)
            ->map(function (UploadedFile $file) use ($productId, &$storedPaths) {
                $extension = Str::lower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
                $filename = Str::uuid().'.'.$extension;
                $path = $file->storeAs('products/'.$productId, $filename, 'public');
                $storedPaths[] = $path;

                return 'storage/'.ltrim($path, '/');
            })
            ->values()
            ->all();
    }

    private function ensurePublishable(Product $product): void
    {
        if (! $product->is_active) {
            return;
        }

        if ((int) $product->price < 1000) {
            throw ValidationException::withMessages([
                'price' => 'Sản phẩm đang hiển thị phải có giá bán hợp lệ.',
            ]);
        }

        if (! $product->images()->exists()) {
            throw ValidationException::withMessages([
                'images' => 'Sản phẩm đang hiển thị phải có ít nhất một ảnh.',
            ]);
        }
    }

    private function syncThumbnail(Product $product): void
    {
        $thumb = $product->images()->orderBy('sort_order')->orderBy('id')->value('url');
        $product->forceFill(['thumb' => $thumb])->save();
    }

    private function recordStockMovement(
        Product $product,
        int $stockBefore,
        int $stockAfter,
        ?int $actorId,
        string $type,
        string $note
    ): void {
        InventoryMovement::query()->create([
            'product_id' => $product->id,
            'order_id' => null,
            'user_id' => $actorId,
            'type' => $type,
            'quantity' => $stockAfter - $stockBefore,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'unit_cost' => $product->cost_price,
            'note' => trim($note),
        ]);
    }

    private function sanitizeDescription(?string $description): ?string
    {
        $html = trim((string) $description);
        if ($html === '') {
            return null;
        }

        $html = preg_replace('#<(script|style|iframe|object|embed|link|meta)\b[^>]*>.*?</\1>#isu', '', $html);
        $html = preg_replace('/\s+(?:on\w+|style|srcdoc)\s*=\s*(["\']).*?\1/isu', '', $html);
        $html = preg_replace('/\s+(?:on\w+|style|srcdoc)\s*=\s*[^\s>]+/isu', '', $html);
        $html = preg_replace('/\s+(href|src)\s*=\s*(["\'])\s*(?:javascript|data):.*?\2/isu', ' $1="#"', $html);

        return strip_tags(
            $html,
            '<p><br><strong><b><em><i><ul><ol><li><table><thead><tbody><tr><th><td><img><a><h2><h3><h4><blockquote>'
        );
    }

    private function nullableText($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function localStoragePath(?string $url): ?string
    {
        $url = ltrim(trim((string) $url), '/');

        return Str::startsWith($url, 'storage/products/')
            ? Str::after($url, 'storage/')
            : null;
    }

    private function deleteStoredPaths(array $paths): void
    {
        if ($paths !== []) {
            Storage::disk('public')->delete(array_values(array_unique($paths)));
        }
    }
}
