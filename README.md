# AutoConf Vehicles API

Laravel 12 JSON API for the AutoConf Vehicles catalog. It uses Sanctum first-party session cookies; it does not issue bearer tokens.

## Local setup

Requirements: PHP 8.2+, Composer, SQLite, and PHP extensions for PDO SQLite, mbstring, XML, cURL, fileinfo, and GD/Imagick.

```bash
cp .env.example .env
composer install
touch database/database.sqlite
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
composer run serve
```

Replace `DB_DATABASE` in `.env` with the absolute path to `database/database.sqlite`. SQLite is the supported database for this challenge. The SPA and API must both use `localhost` (not a mix of `localhost` and `127.0.0.1`) because browser cookies are host-sensitive.

The default origins are API `http://localhost:8080` and SPA `http://localhost:5173`. Set `APP_URL`, `SERVER_HOST`, `SERVER_PORT`, `FRONTEND_URL`, and `SANCTUM_STATEFUL_DOMAINS` together if either changes.

Seed accounts: `admin@example.com` / `password` and `user@example.com` / `password`.

## Authentication and docs

Fetch `GET /sanctum/csrf-cookie`, then send requests with the session cookie and `X-XSRF-TOKEN` header. Login and registration are rate-limited. Generated OpenAPI documentation is available at `/docs/api` and `/docs/api.json` after dependencies are installed.

```bash
vendor/bin/pint --test
php artisan test
php artisan migrate:fresh --seed
```

Public image files live on the `public` disk under `storage/app/public/vehicles`; `storage:link` exposes them at `/storage`.

Each non-empty Vehicle Gallery has exactly one cover image. The gallery lifecycle enforces this during application mutations, while SQLite prevents a Vehicle from having multiple covers.

Image file deletion is attempted immediately after the related database deletion commits. If local storage rejects a deletion, the API still completes the logical deletion and records a warning for operator follow-up.

## API examples

```bash
curl -c cookies.txt http://localhost:8080/sanctum/csrf-cookie
curl -b cookies.txt -c cookies.txt -H 'Content-Type: application/json' -H 'X-XSRF-TOKEN: <token>' \
  -d '{"email":"user@example.com","password":"password"}' http://localhost:8080/api/auth/login
curl -b cookies.txt 'http://localhost:8080/api/vehicles?q=onix&sort=km,-valor_venda'
curl -b cookies.txt -F 'files[]=@vehicle.jpg' http://localhost:8080/api/vehicles/1/images
curl -b cookies.txt -X PATCH http://localhost:8080/api/vehicles/1/images/2/cover
curl -b cookies.txt -X DELETE http://localhost:8080/api/vehicles/1/images/2
```

## Presentation checklist

Start both apps, open OpenAPI documentation, register, create a vehicle, demonstrate validation, upload and promote images, search/filter/sort, test owner/non-owner/admin permissions, inspect audit metadata, and run the test suites.
