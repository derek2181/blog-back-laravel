<?php

namespace App\Services;

use App\Enums\ItemType;
use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ItemsService
{
    public function __construct(
        private readonly ItemCardService $cardService,
        private readonly ItemImageService $imageService
    ) {
    }

    public function findShowcaseByType(string $type, ?int $limit = 6): array
    {
        $normalizedType = strtoupper($type);
        $query = Item::with(['itemImages.imageAsset'])
            ->where('type', $normalizedType)
            ->orderByDesc('postedAt');

        if ($limit !== null) {
            $query->take($limit);
        }

        return $query->get()->map(fn (Item $item) => $this->cardService->toCard($item))->all();
    }

    public function findBlogPosts(?int $limit = null): array
    {
        $query = Item::with(['itemImages.imageAsset'])
            ->where('type', ItemType::BLOG->value)
            ->orderByDesc('postedAt');

        if ($limit !== null) {
            $query->take($limit);
        }

        return $query->get()->map(fn (Item $item) => $this->cardService->toCard($item))->all();
    }

    public function findOne(string $type, int $id): ?array
    {
        $normalizedType = strtoupper($type);
        $item = Item::with(['itemImages.imageAsset'])
            ->where('id', $id)
            ->where('type', $normalizedType)
            ->first();

        return $item ? $this->cardService->toCard($item) : null;
    }

    public function search(string $query, ?array $types, int $page = 1, int $size = 10): array
    {
        $q = strtolower($query ?? '');
        $typeList = $types && count($types) ? $types : ItemType::values();
        $typeList = array_values(array_filter($typeList, fn ($type) => in_array($type, ItemType::values(), true)));
        if (!$typeList) {
            $typeList = ItemType::values();
        }

        $where = function (Builder $builder) use ($q) {
            $builder
                ->where('name', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%")
                ->orWhere('category', 'like', "%{$q}%")
                ->orWhere('tagsJson', 'like', "%{$q}%");
        };

        $validSize = $size > 0 ? min($size, 500) : 10;
        $validPage = $page > 0 ? $page : 1;

        $baseQuery = Item::query()->whereIn('type', $typeList)->where($where);
        $total = $baseQuery->count();

        $items = Item::with(['itemImages.imageAsset'])
            ->whereIn('type', $typeList)
            ->where($where)
            ->orderByDesc('postedAt')
            ->skip(($validPage - 1) * $validSize)
            ->take($validSize)
            ->get()
            ->map(fn (Item $item) => $this->cardService->toCard($item));

        $totalPages = max(1, (int) ceil($total / $validSize));

        return [
            'items' => $items->all(),
            'total' => $total,
            'page' => $validPage,
            'size' => $validSize,
            'totalPages' => $totalPages,
        ];
    }

    public function create(array $data): Item
    {
        $payload = $this->normalizeCreateInput($data);
        $item = Item::create($payload);
        $this->imageService->syncItemImages($item);
        return $item;
    }

    public function update(int $id, array $data): Item
    {
        $payload = $this->normalizeUpdateInput($data);
        $item = Item::findOrFail($id);
        $item->fill($payload);
        $item->save();
        $this->imageService->syncItemImages($item);
        return $item;
    }

    public function remove(int $id): Item
    {
        $item = Item::findOrFail($id);
        $item->delete();
        return $item;
    }

    private function normalizeCreateInput(array $data): array
    {
        $tags = isset($data['tags']) && is_array($data['tags']) ? $data['tags'] : [];
        $gallery = isset($data['gallery']) && is_array($data['gallery']) ? $data['gallery'] : [];

        return [
            'name' => $data['name'],
            'description' => $data['description'],
            'image' => $data['image'],
            'category' => $data['category'],
            'type' => strtoupper($data['type']),
            'releaseYear' => $data['releaseYear'] ?? null,
            'readTime' => $data['readTime'] ?? null,
            'video' => $data['video'] ?? null,
            'postedAt' => $this->parseDate($data['postedAt'] ?? null),
            'updatedAt' => $this->parseDate($data['updatedAt'] ?? null),
            'tagsJson' => array_key_exists('tags', $data) ? implode(',', $tags) : '',
            'galleryJson' => array_key_exists('gallery', $data) ? implode(',', $gallery) : '',
        ];
    }

    private function normalizeUpdateInput(array $data): array
    {
        $payload = [];

        if (array_key_exists('name', $data)) {
            $payload['name'] = $data['name'];
        }
        if (array_key_exists('description', $data)) {
            $payload['description'] = $data['description'];
        }
        if (array_key_exists('image', $data)) {
            $payload['image'] = $data['image'];
        }
        if (array_key_exists('category', $data)) {
            $payload['category'] = $data['category'];
        }
        if (array_key_exists('type', $data)) {
            $payload['type'] = strtoupper($data['type']);
        }
        if (array_key_exists('releaseYear', $data)) {
            $payload['releaseYear'] = $data['releaseYear'];
        }
        if (array_key_exists('readTime', $data)) {
            $payload['readTime'] = $data['readTime'];
        }
        if (array_key_exists('video', $data)) {
            $payload['video'] = $data['video'];
        }
        if (array_key_exists('postedAt', $data)) {
            $payload['postedAt'] = $this->parseDate($data['postedAt']);
        }
        if (array_key_exists('updatedAt', $data)) {
            $payload['updatedAt'] = $this->parseDate($data['updatedAt']);
        }
        if (array_key_exists('tags', $data)) {
            $tags = is_array($data['tags']) ? $data['tags'] : [];
            $payload['tagsJson'] = implode(',', $tags);
        }
        if (array_key_exists('gallery', $data)) {
            $gallery = is_array($data['gallery']) ? $data['gallery'] : [];
            $payload['galleryJson'] = implode(',', $gallery);
        }

        return $payload;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (!$value) {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
