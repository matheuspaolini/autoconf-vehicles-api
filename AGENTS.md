## Agent skills

### Issue tracker

Issues are tracked in this repository's GitHub Issues. See `docs/agents/issue-tracker.md`.

### Triage labels

Uses the default canonical triage labels. See `docs/agents/triage-labels.md`.

### Domain docs

Uses a single-context domain-doc layout. See `docs/agents/domain.md`.

### Vehicle reliability

Before changing Vehicle Gallery mutations, ETags, upload replay, or media
cleanup, read `CONTEXT.md` and `docs/adr/0002-use-postgresql-for-vehicle-concurrency.md`.
Preserve the documented PostgreSQL locking, strong ETag, idempotency, and
after-commit cleanup contracts; extend their feature tests with the change.

### Verification

CI runs formatting, OpenAPI analysis, and the full PostgreSQL-backed test suite
on PHP 8.2–8.4. For application changes, run the closest available subset plus
`vendor/bin/pint --test`; report any unavailable Docker/PostgreSQL dependency
instead of changing the application to bypass it.

## PHP static-analysis hygiene

When writing or refactoring namespaced code in `app/`:

- Call PHP built-in functions through the global namespace (for example, `\array_key_exists()` and `\trim()`). Keep Laravel helpers such as `now()`, `filled()`, `app()`, `abort()`, and `response()` as helpers.
- Prefer a first-class callable (for example, `$this->dispatch(...)`) when a callback only forwards its compatible arguments to one method.
- Declare every directly accessed Eloquent attribute in the model PHPDoc with its persisted/cast type and nullability (for example, `@property string $status`) so `$model->attribute` is statically typed.

When an IDE or static-analysis warning of these kinds appears, search the relevant `app/` scope and remediate equivalent existing occurrences as part of the same change. Validate with PHP syntax checks and `vendor/bin/pint --test`; run the relevant static-analysis inspection when configured.
