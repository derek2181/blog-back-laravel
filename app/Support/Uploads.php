<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class Uploads
{
    public const DISK = 'uploads';

    public static function disk()
    {
        return Storage::disk(self::DISK);
    }

    public static function ensureBaseDirectory(): void
    {
        $publicDisk = Storage::disk('public');
        if (!$publicDisk->exists('uploads')) {
            $publicDisk->makeDirectory('uploads');
        }
    }

    public static function ensurePublicLink(): void
    {
        $link = public_path('uploads');
        $target = storage_path('app/public/uploads');
        if (is_link($link) || File::exists($link)) {
            return;
        }
        try {
            File::link($target, $link);
        } catch (\Throwable) {
            // Symlink may be blocked; fallback routes handle serving.
        }
    }

    public static function publicPath(string $relativePath): string
    {
        return '/uploads/'.ltrim(str_replace('\\', '/', $relativePath), '/');
    }

    public static function isImage(string $path): bool
    {
        return (bool) preg_match('/\.(png|jpe?g|gif|webp|avif)$/i', $path);
    }
}
