<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        DB::table('categories')
            ->select(['id', 'name', 'slogan', 'description', 'meta_title', 'meta_description'])
            ->orderBy('id')
            ->chunkById(100, function ($categories): void {
                foreach ($categories as $category) {
                    $name = trim((string) $category->name);
                    if ($name === '') {
                        continue;
                    }

                    $summary = trim((string) $category->slogan)
                        ?: "Khám phá {$name} tươi ngon, được tuyển chọn kỹ và giao tận nơi mỗi ngày.";
                    $updates = [];

                    if (trim((string) $category->description) === '') {
                        $updates['description'] = $summary;
                    }
                    if (trim((string) $category->meta_title) === '') {
                        $updates['meta_title'] = Str::limit("{$name} | Thế Giới Trái Cây", 60, '');
                    }
                    if (trim((string) $category->meta_description) === '') {
                        $updates['meta_description'] = Str::limit($summary, 155, '');
                    }

                    if ($updates !== []) {
                        DB::table('categories')->where('id', $category->id)->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        // Generated metadata may have been edited in the CMS after deployment.
        // Keep it on rollback instead of deleting potentially curated content.
    }
};
