<?php

namespace App\Http\Controllers;

use App\Enums\ImageFolder;
use App\Models\ImageAsset;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminUploadsController extends Controller
{
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const ALLOWED_EXT = ['.jpg', '.jpeg', '.png', '.webp', '.gif'];
    private const MAX_FILES = 50;
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    public function folders()
    {
        return response()->json(ImageFolder::values());
    }

    public function upload(Request $request)
    {
        $folderKey = strtolower((string) $request->input('folderKey', ''));
        if (!in_array($folderKey, ImageFolder::values(), true)) {
            return response()->json(
                ['message' => 'Selecciona un folder valido.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $files = $request->file('files', []);
        if (!is_array($files)) {
            $files = [$files];
        }

        $errors = [];
        $results = [];

        $seenNames = [];
        $limited = array_slice($files, 0, self::MAX_FILES);

        foreach ($limited as $file) {
            if (!$file || !$file->isValid()) {
                $errors[] = [
                    'originalName' => $file?->getClientOriginalName() ?? 'archivo',
                    'reason' => 'Archivo invalido.',
                ];
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $sanitized = $this->sanitizeFileName($originalName);
            if ($sanitized === '') {
                $errors[] = ['originalName' => $originalName, 'reason' => 'Nombre de archivo invalido.'];
                continue;
            }

            $lowerName = strtolower($sanitized);
            if (isset($seenNames[$lowerName])) {
                $errors[] = ['originalName' => $originalName, 'reason' => 'Nombre duplicado en el lote.'];
                continue;
            }
            $seenNames[$lowerName] = true;

            $ext = strtolower('.'.pathinfo($sanitized, PATHINFO_EXTENSION));
            $mime = strtolower((string) $file->getClientMimeType());
            if (!in_array($mime, self::ALLOWED_MIME, true) || !in_array($ext, self::ALLOWED_EXT, true)) {
                $errors[] = [
                    'originalName' => $originalName,
                    'reason' => 'Solo se permiten imagenes JPEG, PNG, WEBP o GIF.',
                ];
                continue;
            }

            if ($file->getSize() > self::MAX_FILE_SIZE) {
                $errors[] = [
                    'originalName' => $originalName,
                    'reason' => 'Archivo supera 10MB.',
                ];
                continue;
            }

            $storedName = $sanitized;
            $disk = Uploads::disk();
            if (!$disk->exists($folderKey)) {
                $disk->makeDirectory($folderKey);
            }
            if ($disk->exists($folderKey.'/'.$storedName)) {
                $errors[] = [
                    'originalName' => $originalName,
                    'reason' => 'Ya existe una imagen con ese nombre en el folder.',
                ];
                continue;
            }
            $disk->putFileAs($folderKey, $file, $storedName);

            $publicPath = Uploads::publicPath($folderKey.'/'.$storedName);

            try {
                $asset = ImageAsset::updateOrCreate(
                    ['path' => $publicPath],
                    [
                        'folderKey' => $folderKey,
                        'originalName' => $originalName,
                        'mimeType' => $file->getClientMimeType(),
                        'sizeBytes' => $file->getSize(),
                    ]
                );

                $results[] = [
                    'originalName' => $originalName,
                    'storedName' => $storedName,
                    'folderKey' => $folderKey,
                    'path' => $publicPath,
                    'sizeBytes' => $file->getSize(),
                    'mimeType' => $file->getClientMimeType(),
                    'imageAssetId' => $asset->id,
                ];
            } catch (\Throwable) {
                $errors[] = [
                    'originalName' => $originalName,
                    'reason' => 'No se pudo registrar la imagen en la base de datos.',
                ];
            }
        }

        if (!$results && !$errors) {
            return response()->json(
                ['message' => 'No se recibieron imagenes validas.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        return response()->json([
            'files' => $results,
            'errors' => $errors,
        ]);
    }

    private function sanitizeFileName(string $name): string
    {
        $base = str_replace(['/', '\\'], '', $name);
        $base = trim($base);
        return $base;
    }
}
