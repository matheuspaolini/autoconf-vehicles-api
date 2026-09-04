# AutoConf Vehicles API

Laravel 12 JSON API for the AutoConf shared vehicle inventory. It uses Laravel Sanctum first-party session cookies; it does not issue bearer tokens.

## Quick start

Requirements: Docker Engine and Docker Compose.

From this repository:

```bash
docker compose up --build --wait
```

The stack starts PostgreSQL, the API, a queue worker, and the scheduler. It installs PHP dependencies, creates the storage link, and applies migrations automatically.

| Service | Address |
| --- | --- |
| API | `http://localhost:8080` |
| Health check | `http://localhost:8080/up` |
| OpenAPI UI | `http://localhost:8080/docs/api` |
| OpenAPI JSON | `http://localhost:8080/docs/api.json` |
| PostgreSQL | `127.0.0.1:5432` |

The database starts empty. To add the local demo users and vehicles:

```bash
docker compose exec api php artisan db:seed
```

Demo accounts are `admin@example.com` / `password` and `user@example.com` / `password`.

## Work with the local stack

The web app normally runs at `http://localhost:5173` and must call this API at `http://localhost:8080`. Use `localhost` for both browser-facing URLs; do not mix it with `127.0.0.1`, because Sanctum session cookies are host-sensitive.

```bash
# Follow API logs after the stack is running
docker compose logs --follow --tail=0 api

# Run the API checks
docker compose exec api vendor/bin/pint --test
docker compose exec api php artisan scramble:analyze
docker compose exec api php artisan test

# Stop the stack and retain local data
docker compose down
```

To reset all local database, dependency, and upload data, run `docker compose down --volumes`.

## Further reading

- [OpenAPI documentation](http://localhost:8080/docs/api) is the API contract and request reference.
- [Domain context](CONTEXT.md) explains the vehicle catalog vocabulary and invariants.
- [PostgreSQL concurrency decision](docs/adr/0002-use-postgresql-for-vehicle-concurrency.md) documents gallery locking, ETags, idempotency, and cleanup behavior.
- [Agent and contributor guidance](AGENTS.md) describes repository-specific working conventions.
