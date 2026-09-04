# PHP/Laravel Architecture Review

Reviewed: 2026-09-04

## Verdict

**Good for a small Laravel demo application.** The code is more deliberate
than a typical small CRUD application: it has explicit ownership authorization,
request validation, resource-based JSON responses, PostgreSQL-backed gallery
invariants, optimistic write concurrency, idempotent image uploads, and durable
post-commit media cleanup. These are worthwhile demo reliability concerns.

The previous Vehicle read path was mildly over-designed for the application's
size. Five immutable presentation objects and three resources represented one
HTTP projection, so an output-field change crossed multiple shallow modules.
The review removes that bridge: `VehicleRead` now owns relation loading and the
Laravel resources own JSON serialization. The HTTP contract is unchanged.

## Comparison with primary guidance

| Guidance | Repository assessment |
| --- | --- |
| Laravel permits application-specific organization as long as Composer autoloads it, and treats HTTP/console as entry points rather than homes for application logic. [Laravel directory structure](https://laravel.com/docs/12.x/structure) | Good. The focused `Domain/Vehicles` module holds the gallery lifecycle, versioning, upload replay, and media retirement rules; controllers remain orchestration-oriented. |
| Laravel puts controllers, form requests, policies, jobs, and Eloquent models in their conventional locations. [Laravel directory structure](https://laravel.com/docs/12.x/structure), [controllers](https://laravel.com/docs/12.x/controllers), [validation](https://laravel.com/docs/12.x/validation) | Good. The app follows these conventions without a generic repository or container abstraction. |
| Laravel API resources are the intended transformation layer for JSON responses. [Laravel API resources](https://laravel.com/docs/12.x/eloquent-resources) | Improved. Resources now transform the loaded Eloquent models directly; the former DTO-to-resource pass-through was unnecessary at this scale. |
| Queued work and scheduling are first-class Laravel mechanisms; after-commit dispatch prevents queued work from observing uncommitted data. [Laravel queues](https://laravel.com/docs/12.x/queues), [task scheduling](https://laravel.com/docs/12.x/scheduling) | Good. Media cleanup is recorded transactionally, dispatched after commit, retried, and reconciled. Keep it. |
| PSR-4 maps namespaces to filesystem paths. [PSR-4](https://www.php-fig.org/psr/psr-4/) | Good. Application namespaces match their paths; the review renames the read module to `VehicleRead` to match its reduced responsibility. |

## What is intentionally retained

- `VehicleImageLifecycle`: a deep Vehicle Gallery module. Its small interface
  hides locking, capacity, cover promotion, audit updates, and media retirement.
- `VehicleVersion`: a small, cohesive interface for the ETag concurrency
  contract. Deleting it would duplicate parsing and error behaviour at callers.
- `VehicleUploadReplay` and `VehicleMediaRetirement`: operational reliability
  rules with real failure modes. They are not abstraction for abstraction's sake.
- The focused tests around gallery invariants, resource query counts, OpenAPI,
  authorization, idempotency, and cleanup retry behaviour.

## Removed

- The starter `CHANGELOG.md`: it contained only Laravel skeleton release links
  and did not describe this project.
- `VehicleAuditRepresentation`, `VehicleDetailRepresentation`,
  `VehicleImageRepresentation`, and `VehicleListRepresentation`: duplicate
  presentation types with no independent domain role.
- `VehicleRepresentationRead`: replaced by `VehicleRead`, which only loads the
  data that resources need.

## Verification

- `vendor/bin/pint --test` passed.
- PHP syntax checks passed for the edited application files.
- `php artisan test --compact` could not run because PostgreSQL at
  `127.0.0.1:5432` rejected the `autoconf_test` connection (`SQLSTATE[08006]`).
  This is an environment dependency issue, not a failing assertion. The command
  also could not write PHPUnit's result cache due to workspace permissions.
