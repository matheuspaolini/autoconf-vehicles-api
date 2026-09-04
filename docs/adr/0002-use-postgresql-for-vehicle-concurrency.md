# ADR 0002: Use PostgreSQL for Vehicle concurrency

## Context

Vehicle Gallery mutations rely on row-level locks and the API now exposes an
ETag-based write-concurrency contract. SQLite does not provide `FOR UPDATE`
locks through Laravel, so it cannot provide the required behavior.

## Decision

PostgreSQL is the sole supported database. Vehicle Gallery mutations lock the
Vehicle row, and PostgreSQL enforces the single-cover invariant with a partial
unique index. Vehicle writes use a monotonic lock version exposed as a strong
ETag.

## Consequences

- Local development, CI, and production require PostgreSQL and `pdo_pgsql`.
- ADR-0001 is superseded.
- Every mutation of an existing Vehicle requires a current `If-Match` ETag.
