<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertPageRequest;
use App\Services\PagesService;

class AdminPagesController extends Controller
{
    public function __construct(private readonly PagesService $pagesService)
    {
    }

    public function show(string $key)
    {
        return response()->json($this->pagesService->getPage($key));
    }

    public function update(string $key, UpsertPageRequest $request)
    {
        return response()->json($this->pagesService->upsertPage($key, $request->validated()));
    }
}
