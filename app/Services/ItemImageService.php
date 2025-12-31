<?php

namespace App\Services;

use App\Enums\ImageFolder;
use App\Enums\ItemImageRole;
use App\Models\ImageAsset;
use App\Models\Item;
use App\Models\ItemImage;

class ItemImageService
{
    public function syncItemImages(Item $item): void
    {
        $primaryPath = $this->extractUploadsPath($item->image);
        $galleryPaths = $this->parseGallery($item->galleryJson);
        $filteredGallery = array_filter($galleryPaths, fn ($path) => $path && $path !== $primaryPath);
        $uniqueGallery = array_values(array_unique($filteredGallery));

        $desired = [];

        if ($primaryPath) {
            $asset = $this->upsertAsset($primaryPath);
            if ($asset) {
                $desired[] = [
                    'role' => ItemImageRole::PRIMARY->value,
                    'imageAssetId' => $asset->id,
                    'order' => 0,
                ];
            }
        }

        foreach ($uniqueGallery as $index => $path) {
            $asset = $this->upsertAsset($path);
            if ($asset) {
                $desired[] = [
                    'role' => ItemImageRole::GALLERY->value,
                    'imageAssetId' => $asset->id,
                    'order' => $index,
                ];
            }
        }

        if (!$desired) {
            return;
        }

        $existing = ItemImage::where('itemId', $item->id)->get();
        $desiredKeys = collect($desired)->map(fn ($entry) => $entry['role'].':'.$entry['imageAssetId'])->all();
        $desiredSet = array_flip($desiredKeys);

        foreach ($desired as $entry) {
            ItemImage::updateOrCreate(
                [
                    'itemId' => $item->id,
                    'role' => $entry['role'],
                    'imageAssetId' => $entry['imageAssetId'],
                ],
                ['order' => $entry['order']]
            );
        }

        $toDelete = $existing
            ->filter(fn ($entry) => !isset($desiredSet[$entry->role.':'.$entry->imageAssetId]))
            ->pluck('id')
            ->all();

        if ($toDelete) {
            ItemImage::whereIn('id', $toDelete)->delete();
        }
    }

    public function backfill(): void
    {
        $items = Item::all();
        foreach ($items as $item) {
            $this->syncItemImages($item);
        }
    }

    private function upsertAsset(string $path): ?ImageAsset
    {
        $normalized = $this->extractUploadsPath($path);
        if (!$normalized) {
            return null;
        }

        $folderKey = $this->resolveFolderKey($normalized);
        if (!$folderKey) {
            return null;
        }

        return ImageAsset::firstOrCreate(
            ['path' => $normalized],
            ['folderKey' => $folderKey]
        );
    }

    private function extractUploadsPath(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        $idx = strpos($value, '/uploads/');
        if ($idx !== false) {
            return substr($value, $idx);
        }
        return str_starts_with($value, '/uploads/') ? $value : null;
    }

    private function resolveFolderKey(string $path): ?string
    {
        if (str_starts_with($path, '/uploads/albums/')) {
            return ImageFolder::albums->value;
        }
        if (str_starts_with($path, '/uploads/itzy/')) {
            return ImageFolder::itzy->value;
        }
        return null;
    }

    private function parseGallery(?string $value): array
    {
        if (!$value) {
            return [];
        }
        $trimmed = trim($value);
        if ($trimmed === '') {
            return [];
        }

        $asCsv = static function () use ($trimmed): array {
            return array_values(array_filter(array_map('trim', explode(',', $trimmed))));
        };

        if (str_starts_with($trimmed, '[')) {
            try {
                $parsed = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($parsed)) {
                    $items = array_map(static function ($entry) {
                        if (is_string($entry)) {
                            return $entry;
                        }
                        if (is_array($entry)) {
                            return $entry['path'] ?? $entry['image'] ?? $entry['url'] ?? '';
                        }
                        return '';
                    }, $parsed);
                    $items = array_values(array_filter(array_map('trim', $items)));
                    return array_values(array_unique($items));
                }
            } catch (\Throwable) {
                return array_values(array_unique($asCsv()));
            }
        }

        return array_values(array_unique($asCsv()));
    }
}
