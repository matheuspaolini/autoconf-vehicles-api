# ADR 0001: Enforce Vehicle Gallery cover in application and SQLite

## Context

SQLite cannot express the requirement that a non-empty Vehicle Gallery has at
least one Cover using only a partial unique index. Existing gallery data may
also predate the invariant.

## Decision

Vehicle Gallery mutations pass through `VehicleImageLifecycle`, which creates
and promotes Covers as needed. A SQLite partial unique index prevents more than
one Cover for a Vehicle. The migration repairs legacy galleries deterministically
by selecting the lowest-ID Vehicle Image.

## Alternatives rejected

- Application-only enforcement does not protect direct database writes.
- A `cover_image_id` on Vehicles complicates the ownership relationship.
- Database triggers hide lifecycle rules in database-specific behavior.

## Consequences

- SQLite is the supported database.
- Production gallery mutations must use the lifecycle module.
- Legacy gallery data is repaired during migration.
