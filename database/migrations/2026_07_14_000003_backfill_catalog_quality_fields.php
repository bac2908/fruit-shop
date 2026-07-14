<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        DB::table('products')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunkById(100, function ($products) {
                foreach ($products as $product) {
                    $name = preg_replace('/\s+/u', ' ', trim((string) $product->name));
                    $source = trim(strip_tags((string) ($product->short_desc ?: $product->description)));
                    $source = preg_replace('/\s+/u', ' ', $source);
                    $updates = [];

                    if ($name !== $product->name) {
                        $updates['name'] = $name;
                    }
                    if (trim((string) $product->sku) === '') {
                        $updates['sku'] = 'TGC-' . str_pad((string) $product->id, 6, '0', STR_PAD_LEFT);
                    }
                    if (trim((string) $product->meta_title) === '') {
                        $updates['meta_title'] = Str::limit($name . ' | Thế Giới Trái Cây', 60, '');
                    }
                    if (trim((string) $product->meta_description) === '' && $source !== '') {
                        $updates['meta_description'] = Str::limit($source, 155, '');
                    }
                    if ($product->sale_price !== null && ((int) $product->sale_price <= 0 || (int) $product->sale_price >= (int) $product->price)) {
                        $updates['sale_price'] = null;
                    }

                    if ($updates !== []) {
                        $updates['updated_at'] = now();
                        DB::table('products')->where('id', $product->id)->update($updates);
                    }

                    $thumb = trim((string) $product->thumb);
                    if ($thumb !== '' && Schema::hasTable('product_images')) {
                        $imageExists = DB::table('product_images')
                            ->where('product_id', $product->id)
                            ->where('url', $thumb)
                            ->exists();

                        if (!$imageExists) {
                            DB::table('product_images')->insert([
                                'product_id' => $product->id,
                                'url' => $thumb,
                                'sort_order' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            });
    }

    public function down(): void
    {
        // Data normalization is intentionally retained during rollback.
    }
};
