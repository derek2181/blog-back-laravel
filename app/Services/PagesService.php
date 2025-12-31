<?php

namespace App\Services;

use App\Enums\ItemType;
use App\Enums\PageBlockType;
use App\Models\Item;
use App\Models\Page;
use App\Models\PageBlock;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PagesService
{
    private const ALLOWED_KEYS = ['about', 'home', 'blog'];

    public function __construct(private readonly ItemCardService $cardService)
    {
    }

    public function getAboutPage(): array
    {
        return $this->getPage('about');
    }

    public function upsertAboutPage(array $payload): array
    {
        return $this->upsertPage('about', $payload);
    }

    public function getPage(string $key): array
    {
        $normalizedKey = $this->normalizeKey($key);
        $page = $this->getPageByKey($normalizedKey);

        if ($normalizedKey === 'home') {
            $page = $this->applyHomeCopyUpgrades($page);
        }

        if ($normalizedKey === 'home' || $normalizedKey === 'blog') {
            return $this->resolvePageBlocks($normalizedKey, $page);
        }

        return $page;
    }

    public function upsertPage(string $key, array $payload): array
    {
        $normalizedKey = $this->normalizeKey($key);
        $normalizedBlocks = $this->normalizeBlocks($payload['blocks'] ?? []);
        $this->validateBlocks($normalizedBlocks);

        return DB::transaction(function () use ($normalizedKey, $payload, $normalizedBlocks) {
            $page = Page::updateOrCreate(
                ['key' => $normalizedKey],
                ['title' => $payload['title'] ?? null]
            );

            PageBlock::where('pageId', $page->id)->delete();

            foreach ($normalizedBlocks as $index => $block) {
                PageBlock::create([
                    'pageId' => $page->id,
                    'type' => $block['type'],
                    'order' => $block['order'] ?? $index,
                    'content' => $block['content'] ?? null,
                    'imagePath' => $block['imagePath'] ?? $this->extractImage($block['content'] ?? null),
                    'metadata' => $block['metadata'] ?? null,
                ]);
            }

            return $this->getPage($normalizedKey);
        });
    }

    private function normalizeKey(string $key): string
    {
        $normalized = strtolower($key);
        if (!in_array($normalized, self::ALLOWED_KEYS, true)) {
            throw new HttpResponseException(
                response()->json(['message' => 'Pagina desconocida.'], Response::HTTP_BAD_REQUEST)
            );
        }
        return $normalized;
    }

    private function getPageByKey(string $key): array
    {
        $page = Page::with(['blocks' => fn ($query) => $query->orderBy('order')])
            ->where('key', $key)
            ->first();

        if (!$page) {
            return ['key' => $key, 'title' => null, 'blocks' => []];
        }

        $blocks = $page->blocks->map(fn (PageBlock $block) => [
            'id' => $block->id,
            'type' => $block->type,
            'order' => $block->order,
            'content' => $block->content ?? null,
            'imagePath' => $block->imagePath,
            'metadata' => $block->metadata ?? null,
        ])->all();

        return [
            'key' => $page->key,
            'title' => $page->title,
            'blocks' => $blocks,
        ];
    }

    private function normalizeBlocks(array $blocks): array
    {
        $normalized = [];
        foreach ($blocks as $idx => $block) {
            $normalized[] = [
                'type' => $block['type'] ?? null,
                'order' => $block['order'] ?? $idx,
                'imagePath' => $block['imagePath'] ?? $this->extractImage($block['content'] ?? null),
                'content' => $this->sanitizeContent($block),
                'metadata' => $block['metadata'] ?? null,
            ];
        }

        usort($normalized, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        return $normalized;
    }

    private function sanitizeContent(array $block): ?array
    {
        if (!isset($block['content']) || !is_array($block['content'])) {
            return $block['content'] ?? null;
        }

        $content = $block['content'];
        $type = $block['type'] ?? null;

        if ($type === PageBlockType::SHOWCASE->value || $type === PageBlockType::POSTS->value) {
            unset($content['items']);
            $count = $this->normalizeCount($content['count'] ?? 0);
            $content['count'] = $count;
            if (isset($content['mode'])) {
                $content['mode'] = strtolower((string) $content['mode']);
            }
            if (isset($content['itemIds']) && is_array($content['itemIds'])) {
                $content['itemIds'] = $this->normalizeIdList($content['itemIds'], $count);
            }
        }

        return $content;
    }

    private function validateBlocks(array $blocks): void
    {
        $missingGalleryImages = collect($blocks)->contains(function ($block) {
            $type = $block['type'] ?? null;
            if ($type !== PageBlockType::GALLERY->value && $type !== PageBlockType::IMAGE->value) {
                return false;
            }
            $images = $block['content']['images'] ?? null;
            return !is_array($images) || count($images) === 0;
        });

        if ($missingGalleryImages) {
            throw new HttpResponseException(
                response()->json(
                    ['message' => 'Las galerias deben incluir al menos una imagen.'],
                    Response::HTTP_BAD_REQUEST
                )
            );
        }

        $missingHeroImage = collect($blocks)->contains(function ($block) {
            if (($block['type'] ?? null) !== PageBlockType::HERO->value) {
                return false;
            }
            return !$this->extractImage($block['content'] ?? null) && empty($block['imagePath']);
        });

        if ($missingHeroImage) {
            throw new HttpResponseException(
                response()->json(
                    ['message' => 'El hero necesita al menos una imagen.'],
                    Response::HTTP_BAD_REQUEST
                )
            );
        }

        $invalidSelectable = collect($blocks)->contains(fn ($block) => $this->hasInvalidSelection($block));
        if ($invalidSelectable) {
            throw new HttpResponseException(
                response()->json(
                    ['message' => 'Seleccion manual invalida para los bloques de contenido.'],
                    Response::HTTP_BAD_REQUEST
                )
            );
        }
    }

    private function hasInvalidSelection(array $block): bool
    {
        $type = $block['type'] ?? null;
        if ($type !== PageBlockType::SHOWCASE->value && $type !== PageBlockType::POSTS->value) {
            return false;
        }
        $content = $block['content'] ?? [];
        $mode = strtolower((string) ($content['mode'] ?? 'recent'));
        $count = $this->normalizeCount($content['count'] ?? 0);
        $ids = is_array($content['itemIds'] ?? null) ? array_filter($content['itemIds']) : [];
        if ($mode === 'manual' && $count > 0 && count($ids) === 0) {
            return true;
        }
        if ($mode === 'manual' && $count > count($ids)) {
            return true;
        }
        return false;
    }

    private function applyHomeCopyUpgrades(array $page): array
    {
        if (empty($page['blocks'])) {
            return $page;
        }
        $changed = false;
        $blocks = array_map(function ($block) use (&$changed) {
            if (!isset($block['content']) || !is_array($block['content'])) {
                return $block;
            }
            if ($block['type'] === PageBlockType::CTA->value) {
                $content = $block['content'];
                if (($content['eyebrow'] ?? null) === 'Mas cositas') {
                    $content['eyebrow'] = 'Un ratito mas';
                    $changed = true;
                }
                if (($content['title'] ?? null) === 'Hay nuevas historias y playlists en el blog') {
                    $content['title'] = 'Hay nuevas historias y playlists esperandote';
                    $changed = true;
                }
                if (($content['lead'] ?? null) === 'Estoy subiendo notas y listas cada semana. Date una vuelta, es rapido.') {
                    $content['lead'] = 'Cada semana subo notas cortas, fotos y listas que acompanan tus dias. Date una vuelta.';
                    $changed = true;
                }
                $block['content'] = $content;
                return $block;
            }
            if ($block['type'] === PageBlockType::SOCIAL->value) {
                $content = $block['content'];
                if (($content['title'] ?? null) === 'Conecta conmigo') {
                    $content['title'] = 'Nos vemos por aqui';
                    $changed = true;
                }
                if (($content['lead'] ?? null) === 'Tiktok, Instagram y YouTube en el mismo lugar.') {
                    $content['lead'] = 'Sigo compartiendo fotos, mini videos y playlists. Te espero en TikTok, Instagram y YouTube.';
                    $changed = true;
                }
                $block['content'] = $content;
                return $block;
            }
            return $block;
        }, $page['blocks']);

        if (!$changed) {
            return $page;
        }

        return [
            'key' => $page['key'],
            'title' => $page['title'] ?? null,
            'blocks' => $blocks,
        ];
    }

    private function resolvePageBlocks(string $key, array $page): array
    {
        if (empty($page['blocks'])) {
            return $page;
        }

        $blocks = array_map(function ($block) use ($key) {
            if (($block['type'] ?? null) === PageBlockType::SHOWCASE->value) {
                $block['content'] = $this->resolveShowcase($block);
            }
            if (($block['type'] ?? null) === PageBlockType::POSTS->value && $key === 'blog') {
                $block['content'] = $this->resolvePosts($block);
            }
            return $block;
        }, $page['blocks']);

        return [
            'key' => $page['key'],
            'title' => $page['title'] ?? null,
            'blocks' => $blocks,
        ];
    }

    private function resolveShowcase(array $block): array
    {
        $content = is_array($block['content'] ?? null) ? $block['content'] : [];
        $mode = strtolower((string) ($content['mode'] ?? 'recent'));
        $count = $this->normalizeCount($content['count'] ?? 0);
        $itemType = $this->normalizeItemType($content['itemType'] ?? ($content['type'] ?? null));
        $items = $this->resolveItems($mode, $count, $itemType, $content['itemIds'] ?? null);
        return [
            ...$content,
            'mode' => $mode,
            'count' => $count,
            'itemType' => $itemType,
            'items' => $items,
        ];
    }

    private function resolvePosts(array $block): array
    {
        $content = is_array($block['content'] ?? null) ? $block['content'] : [];
        $mode = strtolower((string) ($content['mode'] ?? 'recent'));
        $count = $this->normalizeCount($content['count'] ?? 0);
        $items = $this->resolveItems($mode, $count, ItemType::BLOG->value, $content['itemIds'] ?? null);
        return [
            ...$content,
            'mode' => $mode,
            'count' => $count,
            'items' => $items,
        ];
    }

    private function resolveItems(string $mode, int $count, ?string $type, mixed $itemIds): array
    {
        if ($count <= 0) {
            return [];
        }

        if ($mode === 'manual') {
            $ids = $this->normalizeIdList($itemIds);
            if (!$ids) {
                return [];
            }
            $query = Item::with(['itemImages.imageAsset'])->whereIn('id', $ids);
            if ($type) {
                $query->where('type', $type);
            }
            $items = $query->get();
            $ordered = $this->orderByIds($items, $ids);
            return $ordered->map(fn (Item $item) => $this->cardService->toCard($item))->all();
        }

        $query = Item::with(['itemImages.imageAsset'])->orderByDesc('postedAt')->take($count);
        if ($type) {
            $query->where('type', $type);
        }
        return $query->get()->map(fn (Item $item) => $this->cardService->toCard($item))->all();
    }

    private function orderByIds($items, array $ids)
    {
        $map = [];
        foreach ($items as $item) {
            $map[$item->id] = $item;
        }
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($map[$id])) {
                $ordered[] = $map[$id];
            }
        }
        return collect($ordered);
    }

    private function normalizeItemType(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }
        $normalized = strtoupper((string) $value);
        if (in_array($normalized, ItemType::values(), true)) {
            return $normalized;
        }
        return null;
    }

    private function normalizeIdList(mixed $value, ?int $count = null): array
    {
        if (!is_array($value)) {
            return [];
        }
        $ids = array_values(array_filter(array_map(static function ($id) {
            $num = (int) $id;
            return $num > 0 ? $num : null;
        }, $value)));
        $unique = array_values(array_unique($ids));
        if ($count !== null && count($unique) > $count) {
            return array_slice($unique, 0, $count);
        }
        return $unique;
    }

    private function normalizeCount(mixed $value): int
    {
        $parsed = (int) ($value ?? 0);
        if ($parsed < 0) {
            return 0;
        }
        return $parsed;
    }

    private function extractImage(?array $content): ?string
    {
        if (!$content) {
            return null;
        }
        return $content['imagePath'] ?? $content['image'] ?? null;
    }
}
