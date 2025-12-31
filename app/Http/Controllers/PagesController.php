<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertPageRequest;
use App\Services\PagesService;

class PagesController extends Controller
{
    public function __construct(private readonly PagesService $pagesService)
    {
    }

    public function about()
    {
        return response()->json($this->pagesService->getAboutPage());
    }

    public function home()
    {
        return response()->json($this->pagesService->getPage('home'));
    }

    public function blog()
    {
        return response()->json($this->pagesService->getPage('blog'));
    }

    public function updateAbout(UpsertPageRequest $request)
    {
        return response()->json($this->pagesService->upsertAboutPage($request->validated()));
    }
}
