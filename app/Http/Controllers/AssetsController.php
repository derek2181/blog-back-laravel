<?php

namespace App\Http\Controllers;

use App\Support\Uploads;
use Symfony\Component\HttpFoundation\Response;

class AssetsController extends Controller
{
    public function images()
    {
        try {
            $files = Uploads::disk()->allFiles();
            $images = array_values(array_filter($files, fn ($file) => Uploads::isImage($file)));
            $paths = array_map(fn ($file) => Uploads::publicPath($file), $images);
            return response()->json($paths);
        } catch (\Throwable) {
            return response()->json(
                ['message' => 'No se pudo leer el directorio de imagenes'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function itzyImages()
    {
        try {
            $files = Uploads::disk()->allFiles('itzy');
            $images = array_values(array_filter($files, fn ($file) => Uploads::isImage($file)));
            $items = array_map(function ($file) {
                $normalized = str_replace('\\', '/', $file);
                $name = basename($normalized);
                return ['name' => $name, 'path' => Uploads::publicPath($normalized)];
            }, $images);
            return response()->json($items);
        } catch (\Throwable) {
            return response()->json(
                ['message' => 'No se pudo leer el catalogo de imagenes'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
