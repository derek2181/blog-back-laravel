<?php

namespace App\Services;

use App\Enums\ItemImageRole;
use App\Models\Item;

class ItemCardService
{
    public function toCard(Item $item): array
    {
        $resolved = $this->resolveImages($item);
        $galleryFallback = $this->parseGallery($item->galleryJson);

        $card = [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'image' => $resolved['primary'] ?? $item->image,
            'category' => $item->category,
            'type' => $item->type,
            'tags' => $item->tagsJson ? array_values(array_filter(explode(',', $item->tagsJson))) : [],
            'gallery' => count($resolved['gallery']) ? $resolved['gallery'] : $galleryFallback,
        ];

        if ($item->releaseYear !== null) {
            $card['releaseYear'] = $item->releaseYear;
        }
        if ($item->postedAt) {
            $card['postedAt'] = $item->postedAt->toISOString();
        }
        if ($item->updatedAt) {
            $card['updatedAt'] = $item->updatedAt->toISOString();
        }
        if ($item->readTime !== null) {
            $card['readTime'] = $item->readTime;
        }
        if ($item->video !== null) {
            $card['video'] = $item->video;
        }

        return $card;
    }

    private function resolveImages(Item $item): array
    {
        $images = $item->relationLoaded('itemImages') ? $item->itemImages : [];
        if (empty($images) || count($images) === 0) {
            return ['primary' => null, 'gallery' => []];
        }

        $primary = collect($images)
            ->where('role', ItemImageRole::PRIMARY->value)
            ->sortBy('order')
            ->first();
        $primaryPath = $primary?->imageAsset?->path;

        $gallery = collect($images)
            ->where('role', ItemImageRole::GALLERY->value)
            ->sortBy('order')
            ->map(fn ($img) => $img->imageAsset?->path)
            ->filter()
            ->values()
            ->all();

        $filtered = array_values(array_unique(array_filter($gallery, fn ($path) => $path && $path !== $primaryPath)));

        return ['primary' => $primaryPath, 'gallery' => $filtered];
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
