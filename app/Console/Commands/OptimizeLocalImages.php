<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class OptimizeLocalImages extends Command
{
    protected $signature = 'media:optimize
        {--path=images : Directory inside public/}
        {--quality=82 : WebP quality from 1 to 100}
        {--force : Regenerate WebP files even when they are up to date}';

    protected $description = 'Create WebP variants for local JPEG and PNG assets';

    public function handle(Filesystem $files): int
    {
        if (!extension_loaded('gd') || !function_exists('imagewebp')) {
            $this->error('PHP GD with WebP support is required.');

            return self::FAILURE;
        }

        $quality = (int) $this->option('quality');
        if ($quality < 1 || $quality > 100) {
            $this->error('The --quality value must be between 1 and 100.');

            return self::INVALID;
        }

        $relativePath = trim(str_replace('\\', '/', (string) $this->option('path')), '/');
        $sourceDirectory = public_path($relativePath);
        $publicRoot = realpath(public_path());
        $resolvedDirectory = realpath($sourceDirectory);

        if ($publicRoot === false || $resolvedDirectory === false || !str_starts_with($resolvedDirectory, $publicRoot)) {
            $this->error('The media path must be an existing directory inside public/.');

            return self::INVALID;
        }

        $candidates = collect($files->allFiles($resolvedDirectory))
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png'], true));

        $created = 0;
        $skipped = 0;
        $sourceBytes = 0;
        $outputBytes = 0;

        foreach ($candidates as $file) {
            $source = $file->getPathname();
            $destination = preg_replace('/\.(jpe?g|png)$/i', '.webp', $source);

            if (!is_string($destination)) {
                throw new RuntimeException("Cannot build WebP path for {$source}.");
            }

            if (!$this->option('force') && is_file($destination) && filemtime($destination) >= filemtime($source)) {
                $skipped++;
                continue;
            }

            $contents = file_get_contents($source);
            $image = $contents === false ? false : imagecreatefromstring($contents);

            if ($image === false) {
                $this->warn("Skipped unreadable image: {$source}");
                $skipped++;
                continue;
            }

            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);

            $written = imagewebp($image, $destination, $quality);
            imagedestroy($image);

            if (!$written) {
                $this->error("Failed to write: {$destination}");

                return self::FAILURE;
            }

            $created++;
            $sourceBytes += filesize($source) ?: 0;
            $outputBytes += filesize($destination) ?: 0;
            $this->line('Created ' . str_replace(public_path() . DIRECTORY_SEPARATOR, '', $destination));
        }

        $saving = $sourceBytes > 0 ? round((1 - ($outputBytes / $sourceBytes)) * 100, 1) : 0;
        $this->info("Created {$created} WebP file(s), skipped {$skipped}; generated files are {$saving}% smaller.");

        return self::SUCCESS;
    }
}
