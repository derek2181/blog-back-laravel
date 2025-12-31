<?php

use App\Http\Controllers\AdminPagesController;
use App\Http\Controllers\AdminUploadsController;
use App\Http\Controllers\AssetsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\PagesController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/items/showcase/{type}', [ItemsController::class, 'showcase']);
Route::get('/items/blog', [ItemsController::class, 'blog']);
Route::get('/items/search', [ItemsController::class, 'search']);
Route::get('/items/{type}/{id}', [ItemsController::class, 'show']);

Route::get('/about', [PagesController::class, 'about']);
Route::get('/home', [PagesController::class, 'home']);
Route::get('/blog', [PagesController::class, 'blog']);

Route::get('/assets/images', [AssetsController::class, 'images']);
Route::get('/assets/images/itzy', [AssetsController::class, 'itzyImages']);

Route::middleware('jwt.auth')->group(function () {
    Route::post('/items', [ItemsController::class, 'store']);
    Route::patch('/items/{id}', [ItemsController::class, 'update']);
    Route::delete('/items/{id}', [ItemsController::class, 'destroy']);

    Route::put('/about', [PagesController::class, 'updateAbout']);

    Route::get('/admin/pages/{key}', [AdminPagesController::class, 'show']);
    Route::put('/admin/pages/{key}', [AdminPagesController::class, 'update']);

    Route::get('/admin/upload-folders', [AdminUploadsController::class, 'folders']);
    Route::post('/admin/uploads/images', [AdminUploadsController::class, 'upload']);
});
