# Blog Backend (Laravel 11)

Backend Laravel 11 que replica el API del backend NestJS para el frontend Angular en `blog-web`.

## Requisitos

- PHP 8.2+
- Composer
- MySQL 8+

## Instalacion local

1. Copia el archivo de entorno:
   - `copy .env.example .env`
2. Ajusta variables principales en `.env`:
   - `APP_URL`, `FRONTEND_URL`
   - `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
   - `JWT_SECRET`, `JWT_EXPIRES_IN`
3. Instala dependencias:
   - `composer install`
4. Genera la app key:
   - `php artisan key:generate`
5. Migra y siembra:
   - `php artisan migrate`
   - `php artisan db:seed`

## Endpoints principales

- `POST /auth/login`
- `GET /items/showcase/{type}`
- `GET /items/blog`
- `GET /items/search`
- `GET /items/{type}/{id}`
- `GET /about`
- `GET /home`
- `GET /blog`
- `GET /assets/images`
- `GET /assets/images/itzy`
- Rutas admin: `/items`, `/admin/pages/{key}`, `/admin/uploads/images`

## Uploads

- Los archivos se guardan en `storage/app/public/uploads/<folderKey>`.
- La ruta publica siempre es `/uploads/...`.
- Ejecuta `php artisan storage:link` para crear el symlink `public/uploads` (o crea el link manualmente).

## Seed data

Los JSON originales estan en `database/seed-data/` y se cargan en el seeder.

- `SEED_RESET=true` limpia tablas antes de sembrar (no usar en production).

## CORS

Configura `FRONTEND_URL` (y opcional `FRONTEND_URLS` / `CORS_ORIGINS`) para permitir el dominio del frontend.
