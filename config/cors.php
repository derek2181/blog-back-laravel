<?php

$origins = [];

$addOrigins = function (?string $value) use (&$origins): void {
    if (!$value) {
        return;
    }
    foreach (explode(',', $value) as $origin) {
        $origin = trim($origin);
        if ($origin !== '') {
            $origins[] = $origin;
        }
    }
};

$addOrigins(env('FRONTEND_URL'));
$addOrigins(env('FRONTEND_URLS'));
$addOrigins(env('CORS_ORIGINS'));

if ((env('APP_ENV') ?? 'local') !== 'production') {
    $addOrigins('http://localhost:4200,http://127.0.0.1:4200');
}

return [
    'paths' => ['*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_unique($origins)),
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
