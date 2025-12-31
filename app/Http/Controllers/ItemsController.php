<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Services\ItemsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ItemsController extends Controller
{
    public function __construct(private readonly ItemsService $itemsService)
    {
    }

    public function showcase(string $type, Request $request)
    {
        $limit = $request->query('limit');
        $parsedLimit = ($limit === null || $limit === '') ? null : (int) $limit;
        return response()->json(
            $this->itemsService->findShowcaseByType($type, $parsedLimit ?? 6)
        );
    }

    public function blog(Request $request)
    {
        $limit = $request->query('limit');
        $parsedLimit = ($limit === null || $limit === '') ? null : (int) $limit;
        return response()->json($this->itemsService->findBlogPosts($parsedLimit));
    }

    public function search(Request $request)
    {
        $typesParam = $request->query('types');
        $types = null;
        if ($typesParam) {
            $types = array_filter(array_map('trim', explode(',', (string) $typesParam)));
            $types = array_map('strtoupper', $types);
        }
        $page = max((int) $request->query('page', 1), 1);
        $size = max((int) $request->query('size', 10), 1);
        $q = (string) $request->query('q', '');

        return response()->json($this->itemsService->search($q, $types, $page, $size));
    }

    public function show(string $type, int $id)
    {
        $item = $this->itemsService->findOne($type, $id);
        return response()->json($item);
    }

    public function store(StoreItemRequest $request)
    {
        $item = $this->itemsService->create($request->validated());
        return response()->json($item, Response::HTTP_CREATED);
    }

    public function update(UpdateItemRequest $request, int $id)
    {
        $item = $this->itemsService->update($id, $request->validated());
        return response()->json($item);
    }

    public function destroy(int $id)
    {
        $item = $this->itemsService->remove($id);
        return response()->json($item);
    }
}
