# AutoConf Vehicles API

Laravel 12 JSON API for the AutoConf Vehicles catalog. It uses Sanctum first-party session cookies; it does not issue bearer tokens.

## Local setup with Docker

Requirements: Docker Engine and Docker Compose.

From the repository root:

```bash
docker compose up --build --wait
```

This starts PostgreSQL, installs PHP dependencies in a Docker volume, applies pending migrations, and starts the API, queue worker, and scheduler. The command returns only after PostgreSQL and the API health endpoint are healthy.

To attach to new API logs after startup completes:

```bash
docker compose up --build --wait && docker compose logs --follow --tail=0 api
```

Press `Ctrl+C` to stop following logs; the containers continue running. Use `--tail=100` instead of `--tail=0` to include recent startup output.

- API: `http://localhost:8080`
- Health endpoint: `http://localhost:8080/up`
- OpenAPI UI: `http://localhost:8080/docs/api`
- PostgreSQL: `127.0.0.1:5432`
- Frontend development server: `http://localhost:5173`

The API keeps using `localhost:8080`, so the frontend's `VITE_API_BASE_URL=http://localhost:8080` continues to work. Do not mix `localhost` and `127.0.0.1` in browser-facing API or frontend URLs because Sanctum session cookies are host-sensitive.

Compose supplies a fixed, local-only development application key when `APP_KEY` is absent. Do not reuse it outside local development.

The Docker stack creates the schema only; it does not seed demo data. Register a user through the API or frontend, or seed explicitly after the stack is healthy:

```bash
docker compose exec api php artisan db:seed
```

Seed accounts: `admin@example.com` / `password` and `user@example.com` / `password`.

Stop containers while retaining database, dependency, and upload data:

```bash
docker compose down
```

Remove all local Docker data and start from an empty database:

```bash
docker compose down --volumes
```

After changing `composer.lock`, refresh dependencies and restart application processes:

```bash
docker compose run --rm --no-deps dependencies
docker compose restart api queue scheduler
```

After adding a migration while the stack is already running:

```bash
docker compose run --rm --no-deps migrate
```

Source code is bind-mounted, so HTTP request changes are visible without rebuilding. Laravel queue workers are long-lived; restart `queue` after changing queued job code.

## Manual local setup

Requirements: PHP 8.2+, Composer, Docker Compose, PostgreSQL PDO (`pdo_pgsql`), mbstring, XML, cURL, fileinfo, and GD/Imagick.

```bash
cp .env.example .env
docker compose -f docker/compose.dev.yaml up -d --wait
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve --host=localhost --port=8080
php artisan queue:work
php artisan schedule:work
```

The development Compose service exposes PostgreSQL on `127.0.0.1:5432`. `docker/compose.prod.yaml` defines PostgreSQL only; deploy the API separately on the same private network and set `DB_HOST=postgres`. The SPA and API must both use `localhost` (not a mix of `localhost` and `127.0.0.1`) because browser cookies are host-sensitive.

On its first initialization, the development database also creates `autoconf_test` for PHPUnit. If the `postgres_data` volume already exists from before this setup, create it once with:

```bash
docker compose -f docker/compose.dev.yaml exec -T postgres createdb -U autoconf autoconf_test
```

The default origins are API `http://localhost:8080` and SPA `http://localhost:5173`. If either changes, update `APP_URL`, `FRONTEND_URL`, and `SANCTUM_STATEFUL_DOMAINS`, and pass the matching host and port to `artisan serve`.

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
