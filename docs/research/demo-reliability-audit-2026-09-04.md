# Demo Reliability Audit — 2026-09-04

## Scope

This audit covers the local AutoConf Vehicles demo, its PostgreSQL test suite,
and its Docker-based development workflow. Deployment concerns are intentionally
outside this repository's scope.

## Resolved Findings

### Atomic upload replay

The successful Vehicle Gallery mutation and idempotency replay record now commit
in one database transaction. A failure while recording the replay response rolls
back image rows and the Vehicle version; the request's public files and pending
claim are then removed. Retrying the same key starts from a clean state.

### Public-file orphan recovery

The scheduled media reconciler now also scans only `vehicles/` on the public
disk. It removes files that are not referenced by `vehicle_images` and are older
than one hour, preserving files created by an in-flight upload.

### Test coverage

Feature tests exercise CSRF-protected auth requests, asynchronous cleanup job
execution, upload rollback, upload replay, and safe orphan collection. OpenAPI
schema assertions follow referenced schemas rather than assuming every property
is inline.

## Retained Design

Logical image deletion creates a durable `MediaCleanupTask` inside the database
transaction and dispatches `CleanupVehicleMedia` after commit. This keeps an
HTTP mutation fast and makes filesystem cleanup retryable without an extra event
layer.

## Official References

- [Laravel queues: jobs and database transactions](https://laravel.com/docs/12.x/queues#jobs-and-database-transactions)
  documents after-commit dispatch, which prevents workers from observing rolled
  back or uncommitted records.
- [Laravel events: queued listeners](https://laravel.com/docs/12.x/events#queued-event-listeners)
  confirms that a queued listener is queue-backed work; this demo uses a direct
  job because it has one explicit cleanup responsibility.
- [Laravel filesystem](https://laravel.com/docs/12.x/filesystem) documents the
  disk abstraction used for listing, checking timestamps, and deleting orphaned
  files.
- [Laravel database transactions](https://laravel.com/docs/12.x/database#database-transactions)
  documents the transaction boundary used to persist the gallery mutation and
  completed replay together.

## Verification

Run the PostgreSQL-backed tests, formatting check, OpenAPI analysis, and Composer
advisory audit before presenting the demo:

```bash
docker compose exec -T api php artisan test
docker compose exec -T api vendor/bin/pint --test
docker compose exec -T api php artisan scramble:analyze
composer audit --locked --no-interaction
```
