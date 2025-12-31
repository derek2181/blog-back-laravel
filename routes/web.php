<?php

use Illuminate\Support\Facades\Route;
use App\Support\Uploads;
use Symfony\Component\HttpFoundation\Response;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/uploads/{path}', function (string $path) {
    $path = ltrim($path, '/');
    if ($path === '' || str_contains($path, '..')) {
        abort(Response::HTTP_NOT_FOUND);
    }
    $disk = Uploads::disk();
    if (!$disk->exists($path)) {
        abort(Response::HTTP_NOT_FOUND);
    }
    $fullPath = $disk->path($path);
    return response()->file($fullPath);
})->where('path', '.*');
