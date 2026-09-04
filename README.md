# AutoConf Vehicles API

Laravel 12 JSON API for the AutoConf Vehicles catalog. It uses Sanctum first-party session cookies; it does not issue bearer tokens.

## Local setup

Requirements: PHP 8.2+, Composer, Docker Compose, PostgreSQL PDO (`pdo_pgsql`), mbstring, XML, cURL, fileinfo, and GD/Imagick.

```bash
cp .env.example .env
docker compose -f docker/compose.dev.yaml up -d
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
php artisan queue:work
php artisan schedule:work
```

The development Compose service exposes PostgreSQL on `127.0.0.1:5432`. Production uses `docker/compose.prod.yaml`; provide production credentials and set `DB_HOST=postgres` so the API reaches PostgreSQL over the private Compose network. The SPA and API must both use `localhost` (not a mix of `localhost` and `127.0.0.1`) because browser cookies are host-sensitive.

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

Each Vehicle Gallery holds at most 20 images. A non-empty gallery has exactly one cover image. The gallery lifecycle enforces this during application mutations, while PostgreSQL prevents a Vehicle from having multiple covers.

Image file cleanup runs through the database queue after the logical deletion commits. Failed cleanup remains visible in the queue and cleanup-task records, and the scheduled reconciler requeues unfinished tasks every 15 minutes.

## API examples

```bash
curl -c cookies.txt http://localhost:8080/sanctum/csrf-cookie
curl -b cookies.txt -c cookies.txt -H 'Content-Type: application/json' -H 'X-XSRF-TOKEN: <token>' \
  -d '{"email":"user@example.com","password":"password"}' http://localhost:8080/api/auth/login
curl -b cookies.txt 'http://localhost:8080/api/vehicles?q=onix&sort=km,-valor_venda'
# First read returns ETag: "vehicle-1-v1". Send it on every existing-Vehicle mutation.
curl -i -b cookies.txt http://localhost:8080/api/vehicles/1
curl -b cookies.txt -H 'X-XSRF-TOKEN: <token>' -H 'If-Match: "vehicle-1-v1"' \
  -H 'Idempotency-Key: <uuid>' -F 'files[]=@vehicle.jpg' http://localhost:8080/api/vehicles/1/images
curl -b cookies.txt -H 'X-XSRF-TOKEN: <token>' -H 'If-Match: "vehicle-1-v2"' \
  -X PATCH http://localhost:8080/api/vehicles/1/images/2/cover
curl -b cookies.txt -H 'X-XSRF-TOKEN: <token>' -H 'If-Match: "vehicle-1-v3"' \
  -X DELETE http://localhost:8080/api/vehicles/1/images/2
```

`If-Match` is required for every mutation of an existing Vehicle. A missing header
returns `428`; a stale or invalid ETag returns `412`. Uploads also require a UUID
`Idempotency-Key`. Reuse that key only to retry the exact same upload batch for up
to 24 hours; a completed retry returns the original response without duplicating
files or image rows.

## Presentation checklist

Start both apps, open OpenAPI documentation, register, create a vehicle, demonstrate validation, upload and promote images, search/filter/sort, test owner/non-owner/admin permissions, inspect audit metadata, and run the test suites.
