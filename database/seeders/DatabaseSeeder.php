<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Item;
use App\Models\ItemImage;
use App\Models\ImageAsset;
use App\Models\Page;
use App\Models\PageBlock;
use App\Services\ItemImageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $shouldReset = env('SEED_RESET') === 'true';
        if ($shouldReset && app()->environment('production')) {
            throw new \RuntimeException('SEED_RESET no esta permitido en production.');
        }

        if ($shouldReset) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            PageBlock::truncate();
            Page::truncate();
            ItemImage::truncate();
            ImageAsset::truncate();
            Item::truncate();
            User::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $users = $this->readSeedFile('users.json');
        $items = $this->readSeedFile('items.json');
        $pages = $this->readSeedFile('pages.json');
        $blocks = $this->readSeedFile('page-blocks.json');

        $this->seedUsers($users);
        $this->seedItems($items, $shouldReset);
        app(ItemImageService::class)->backfill();
        $this->seedPagesAndBlocks($pages, $blocks, $shouldReset);
    }

    private function readSeedFile(string $fileName): array
    {
        $filePath = database_path('seed-data'.DIRECTORY_SEPARATOR.$fileName);
        if (!File::exists($filePath)) {
            Log::warning("Seed file missing: {$filePath}");
            return [];
        }

        $raw = File::get($filePath);
        $parsed = json_decode($raw, true);
        if (!is_array($parsed)) {
            throw new \RuntimeException("Seed file {$fileName} must export an array");
        }
        return $parsed;
    }

    private function normalizeStringArray(mixed $value): array
    {
        if (!$value) {
            return [];
        }
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', array_map('strval', $value))));
        }
        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }

    private function normalizeGallery(mixed $value): array
    {
        if (!$value) {
            return [];
        }
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', array_map('strval', $value))));
        }
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return [];
        }

        if (Str::startsWith($trimmed, '[')) {
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
            } catch (Throwable) {
                return $this->normalizeStringArray($trimmed);
            }
        }

        return $this->normalizeStringArray($trimmed);
    }

    private function toDate(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }
        $date = strtotime((string) $value);
        if ($date === false) {
            return null;
        }
        return date('Y-m-d H:i:s', $date);
    }

    private function normalizeItemType(string $value): string
    {
        return strtoupper($value);
    }

    private function seedUsers(array $users): void
    {
        foreach ($users as $user) {
            $email = $user['email'] ?? null;
            if (!$email) {
                continue;
            }
            $role = $user['role'] ?? 'ADMIN';
            $passwordHash = $user['passwordHash'] ?? null;
            if (!$passwordHash && !empty($user['password'])) {
                $passwordHash = Hash::make($user['password']);
            }
            if ($email === 'ichi') {
                $passwordHash = Hash::make('BiggiePapu');
            }
            if (!$passwordHash) {
                Log::warning("Skipping user {$email}: missing password/passwordHash");
                continue;
            }

            User::updateOrCreate(
                ['email' => $email],
                ['password' => $passwordHash, 'role' => $role]
            );
        }
    }

    private function seedItems(array $items, bool $shouldReset): void
    {
        foreach ($items as $item) {
            $name = $item['name'] ?? null;
            $typeRaw = $item['type'] ?? null;
            if (!$name || !$typeRaw) {
                continue;
            }

            $type = $this->normalizeItemType((string) $typeRaw);
            $tags = $this->normalizeStringArray($item['tags'] ?? ($item['tagsJson'] ?? null));
            $gallery = $this->normalizeGallery($item['gallery'] ?? ($item['galleryJson'] ?? null));

            $data = [
                'name' => $name,
                'description' => $item['description'] ?? '',
                'image' => $item['image'] ?? '',
                'category' => $item['category'] ?? '',
                'type' => $type,
                'releaseYear' => $item['releaseYear'] ?? null,
                'postedAt' => $this->toDate($item['postedAt'] ?? null),
                'updatedAt' => $this->toDate($item['updatedAt'] ?? null),
                'readTime' => $item['readTime'] ?? null,
                'video' => $item['video'] ?? null,
                'tagsJson' => implode(',', $tags),
                'galleryJson' => implode(',', $gallery),
            ];

            $existing = Item::where('name', $name)->where('type', $type)->first();
            if ($existing) {
                if ($shouldReset) {
                    $existing->fill($data)->save();
                }
                continue;
            }

            Item::create($data);
        }
    }

    private function seedPagesAndBlocks(array $pages, array $blocks, bool $shouldReset): void
    {
        $pageMap = [];

        foreach ($pages as $page) {
            $key = $page['key'] ?? null;
            if (!$key) {
                continue;
            }
            $record = Page::updateOrCreate(
                ['key' => $key],
                $shouldReset ? ['title' => $page['title'] ?? null] : []
            );
            $pageMap[$key] = $record->id;
        }

        $blocksByPage = [];
        foreach ($blocks as $block) {
            $pageKey = $block['pageKey'] ?? null;
            if (!$pageKey) {
                continue;
            }
            $blocksByPage[$pageKey][] = $block;
        }

        foreach ($blocksByPage as $pageKey => $pageBlocks) {
            $pageId = $pageMap[$pageKey] ?? null;
            if (!$pageId) {
                continue;
            }
            $existingCount = PageBlock::where('pageId', $pageId)->count();
            if ($existingCount > 0 && !$shouldReset) {
                continue;
            }
            if ($shouldReset) {
                PageBlock::where('pageId', $pageId)->delete();
            }

            foreach ($pageBlocks as $idx => $block) {
                $type = strtoupper((string) ($block['type'] ?? ''));
                $order = is_numeric($block['order'] ?? null) ? (int) $block['order'] : $idx + 1;
                PageBlock::create([
                    'pageId' => $pageId,
                    'type' => $type,
                    'order' => $order,
                    'content' => $block['content'] ?? null,
                    'imagePath' => $block['imagePath'] ?? null,
                    'metadata' => $block['metadata'] ?? null,
                ]);
            }
        }
    }
}
