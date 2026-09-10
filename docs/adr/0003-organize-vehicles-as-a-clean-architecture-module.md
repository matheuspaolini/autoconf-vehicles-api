# ADR 0003: Organize Vehicles as a Clean Architecture module

The Vehicle capability is implemented as one modular-monolith boundary under
`App\Modules\Vehicles`. Its framework-independent Domain owns the Vehicle
aggregate and Vehicle Gallery invariants; Application exposes explicit command
and query handlers with typed inputs and outcomes; Infrastructure implements
application-owned persistence and side-effect ports; Presentation translates
HTTP and console protocols. Dependencies point inward from Presentation and
Infrastructure through Application to Domain and are enforced by Deptrac.

The Vehicle aggregate is the consistency boundary for details, version,
authorization, audit information, and gallery membership and cover selection.
Eloquent records are persistence representations mapped to and from that
aggregate by one mapper. Reads use API-neutral projections because catalog and
detail queries do not need aggregate behavior. This deliberate CQRS split keeps
write invariants centralized without forcing HTTP resources to depend on
Eloquent or duplicating persistence models in the API contract.

Application ports are cohesive capability seams rather than generic
repositories or one interface per table. Expected failures are returned as
closed application result types; framework exceptions and HTTP status mapping
remain in Presentation. PostgreSQL row locks and named unique constraints remain
authoritative for concurrent writes as established by ADR-0002.

Vehicle/image deletion records durable cleanup intent in the same database
transaction as the aggregate mutation. Queue dispatch happens only after
commit, and reconciliation redispatches overdue work and removes only old,
unreferenced media. Upload replay claims preserve their established ordering:
a completed replay is returned before ETag validation, while image persistence
and replay completion commit atomically.

## Consequences

- Vehicle HTTP, console, queue, persistence, replay, and media code lives inside
  the module boundary; authentication remains outside and is represented inside
  the module by an actor snapshot.
- Controllers use application handlers and result types, never infrastructure
  adapters.
- Domain and Application cannot depend on Laravel, Eloquent, Symfony HTTP,
  storage, queues, or database APIs.
- The public routes, JSON schemas, validation keys, strong ETags, PostgreSQL
  schema, and stored data remain compatible.
