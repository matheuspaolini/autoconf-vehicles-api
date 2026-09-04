# Docker Compose Local API Environment

Reviewed: 2026-09-04

## Repository findings

The API is a Laravel 12 application configured with a PostgreSQL database queue.
`bootstrap/app.php` exposes the HTTP health route at `/up` and schedules
`media:reconcile-cleanup` every fifteen minutes. Therefore a complete local
runtime needs a web process, a `queue:work` process, and a `schedule:work`
process in addition to PostgreSQL.

The existing `docker/compose.dev.yaml` and `docker/compose.prod.yaml` define
PostgreSQL only. The root `compose.yaml` is a separate Docker-first local entry
point; the existing Compose files and the host-PHP workflow remain available.

The current `DatabaseSeeder` creates fixed email addresses, so it is not safe to
run automatically against a persisted database. The Docker migration service
runs `php artisan migrate --force` without `--seed`; developers may invoke the
seeder explicitly when demo data is wanted.

## Startup design

Docker Compose starts dependencies in order but normally only waits until a
container is running. Its long-form `depends_on` supports
`service_healthy` and `service_completed_successfully`, allowing this explicit
chain:

```text
PostgreSQL healthy + Composer dependencies complete
  -> migrations complete
  -> API, queue worker, and scheduler start
```

PostgreSQL uses `pg_isready` as its healthcheck. The migration service waits for
that healthcheck, and application processes wait for migrations to succeed. The
API itself healthchecks `GET /up`, so `docker compose up --build --wait` only
returns when PostgreSQL and the HTTP application are healthy. Queue and
scheduler are foreground, long-running processes and are intentionally checked
through their running state after migration rather than an artificial HTTP probe.

The stack bind-mounts source code for local editing while storing Composer
dependencies and public uploads in named volumes. The dependency service
fingerprints `composer.lock`, avoiding an unnecessary install on normal startup
and providing an explicit refresh command after dependency changes.

## Official sources

- [Docker Compose startup order](https://docs.docker.com/compose/how-tos/startup-order/) documents the default startup behavior and the `service_healthy` and `service_completed_successfully` dependency conditions.
- [`docker compose up` reference](https://docs.docker.com/reference/cli/docker/compose/up/) documents `--wait`, which waits for services to be running or healthy.
- [Docker build best practices](https://docs.docker.com/build/building/best-practices/) and [Docker build cache optimization](https://docs.docker.com/build/cache/optimize/) support a focused reusable image and a small build context.
- [Official PHP Docker image documentation](https://hub.docker.com/_/php/) documents installing PHP extensions with the image-provided extension helpers and required native dependencies.
- [PostgreSQL `pg_isready`](https://www.postgresql.org/docs/current/app-pg-isready.html) documents the PostgreSQL readiness utility used by the database healthcheck.
- [Laravel scheduling](https://laravel.com/docs/12.x/scheduling#running-the-scheduler-locally) documents `schedule:work` as the foreground local scheduler process.
- [Laravel queues](https://laravel.com/docs/12.x/queues#the-queue-work-command) documents `queue:work` as a long-running worker and notes that workers should be restarted after relevant code changes.
