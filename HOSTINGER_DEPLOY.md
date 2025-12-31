# Deploy en Hostinger (shared hosting)

## Document root

Opcion recomendada:
- Configura el document root para apuntar a `blog-back-laravel/public`.

Si no puedes cambiar el document root:
1. Copia el contenido de `public/` a `public_html/`.
2. Ajusta `public_html/index.php` para apuntar al proyecto:

```php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
```

3. Asegura que `storage/` y `bootstrap/cache/` sigan fuera de `public_html`.

## Variables de entorno necesarias (.env)

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://tu-dominio.com`
- `APP_KEY=base64:...` (generada con `php artisan key:generate`)
- `DB_CONNECTION=mysql`
- `DB_HOST=...`
- `DB_PORT=3306`
- `DB_DATABASE=...`
- `DB_USERNAME=...`
- `DB_PASSWORD=...`
- `JWT_SECRET=...`
- `JWT_EXPIRES_IN=3600s`
- `FRONTEND_URL=https://tu-frontend.com`
- `FRONTEND_URLS=` (opcional)
- `CORS_ORIGINS=` (opcional)
- `SEED_RESET=false`

## Migraciones y seed

Con SSH:
1. `composer install --no-dev --optimize-autoloader`
2. `php artisan key:generate`
3. `php artisan migrate --force`
4. `php artisan db:seed --force`

Sin SSH (alternativa):
- Ejecuta `php artisan migrate` y `php artisan db:seed` en local contra la misma base de datos (si es accesible),
  o exporta el SQL desde local y lo importas via phpMyAdmin.

## Uploads persistentes

- Las imagenes se guardan en `storage/app/public/uploads`.
- Crea el symlink `public/uploads` -> `storage/app/public/uploads` si tu host lo permite.
- Si no se permiten symlinks, deja habilitada la ruta `/uploads/*` en Laravel (ya incluida) y asegúrate de que
  `storage/` tenga permisos de lectura.
- Las rutas publicas deben verse como `/uploads/<folderKey>/<archivo>`.
