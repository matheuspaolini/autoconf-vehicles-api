# OpenAPI contract audit — 2026-09-04

## Scope and method

This is a source-led audit of the generated Scramble document, not a replacement
for running the generator. It compares the route/configuration, controller and
domain behaviour, resources, and the repository's OpenAPI feature test. There
is no committed generated `api.json` artifact in the repository, so claims about
what is currently covered are limited to the declared generation inputs and the
assertions that protect the generated response.

## Findings

### 1. The required CSRF bootstrap call is not an OpenAPI operation

The documentation tells consumers to call `GET /sanctum/csrf-cookie` before
registration, login, and unsafe requests ([`config/scramble.php:30-34`](../../config/scramble.php#L30-L34)).
However, Scramble is configured to discover only routes beginning with `api`
([`config/scramble.php:6-12`](../../config/scramble.php#L6-L12)), while the
application registers `routes/api.php` as the API route file
([`bootstrap/app.php:10-14`](../../bootstrap/app.php#L10-L14)). The CSRF URL is
only referenced in CORS configuration ([`config/cors.php:1-5`](../../config/cors.php#L1-L5)),
not declared by this route file. The OpenAPI feature test likewise checks only
that the prose mentions it ([`tests/Feature/OpenApiDocumentationTest.php:31-44`](../../tests/Feature/OpenApiDocumentationTest.php#L31-L44)).

**Impact:** a client generated from `/docs/api.json` cannot discover or invoke
the prerequisite endpoint from the contract. Add a documented operation (or
explicitly document this external Sanctum endpoint in an extension) and assert
its path, response cookies, and relationship to the unsafe operations.

### 2. Optimistic-concurrency outcomes are under-specified and unprotected by documentation tests

The five vehicle-mutating endpoints advertise a required `If-Match` request
header in their controllers: update and delete
([`app/Http/Controllers/VehicleController.php:71-124`](../../app/Http/Controllers/VehicleController.php#L71-L124)),
upload ([`app/Http/Controllers/UploadVehicleImagesController.php:20-46`](../../app/Http/Controllers/UploadVehicleImagesController.php#L20-L46)),
set cover ([`app/Http/Controllers/SetVehicleCoverController.php:16-29`](../../app/Http/Controllers/SetVehicleCoverController.php#L16-L29)),
and delete image ([`app/Http/Controllers/DeleteVehicleImageController.php:15-28`](../../app/Http/Controllers/DeleteVehicleImageController.php#L15-L28)).
The shared implementation returns **428** when it is absent and **412** when it
is malformed or stale ([`app/Domain/Vehicles/VehicleVersion.php:21-42`](../../app/Domain/Vehicles/VehicleVersion.php#L21-L42)).
The feature suite proves both outcomes for update
([`tests/Feature/VehicleReliabilityTest.php:21-40`](../../tests/Feature/VehicleReliabilityTest.php#L21-L40)),
but the OpenAPI test does not assert either status on any operation
([`tests/Feature/OpenApiDocumentationTest.php:12-70`](../../tests/Feature/OpenApiDocumentationTest.php#L12-L70)).

**Impact:** the documentation can describe the input header while omitting the
protocol clients need to recover from an absent or stale version. Explicitly
declare 428 and 412 for each affected operation, then test their presence and
descriptions in the document.

### 3. ETag response headers are part of the runtime protocol but not covered by the OpenAPI test

Single-vehicle reads return the current ETag
([`app/Http/Controllers/VehicleController.php:61-69`](../../app/Http/Controllers/VehicleController.php#L61-L69));
create and update also return it ([`app/Http/Controllers/VehicleController.php:46-59`](../../app/Http/Controllers/VehicleController.php#L46-L59),
[`app/Http/Controllers/VehicleController.php:84-103`](../../app/Http/Controllers/VehicleController.php#L84-L103)).
Gallery list, upload, cover selection, and image deletion do the same
([`app/Http/Controllers/VehicleImageIndexController.php:13-22`](../../app/Http/Controllers/VehicleImageIndexController.php#L13-L22),
[`app/Http/Controllers/UploadVehicleImagesController.php:56-77`](../../app/Http/Controllers/UploadVehicleImagesController.php#L56-L77),
[`app/Http/Controllers/SetVehicleCoverController.php:29-37`](../../app/Http/Controllers/SetVehicleCoverController.php#L29-L37),
[`app/Http/Controllers/DeleteVehicleImageController.php:28-35`](../../app/Http/Controllers/DeleteVehicleImageController.php#L28-L35)).
Tests verify representative runtime ETags ([`tests/Feature/VehicleDetailResourceTest.php:29-51`](../../tests/Feature/VehicleDetailResourceTest.php#L29-L51),
[`tests/Feature/VehicleReliabilityTest.php:21-40`](../../tests/Feature/VehicleReliabilityTest.php#L21-L40),
[`tests/Feature/VehicleReliabilityTest.php:83-100`](../../tests/Feature/VehicleReliabilityTest.php#L83-L100)),
whereas the OpenAPI test asserts only request parameters, schemas, and one 429
response ([`tests/Feature/OpenApiDocumentationTest.php:31-69`](../../tests/Feature/OpenApiDocumentationTest.php#L31-L69)).

**Impact:** generated clients may have no typed way to read the ETag needed for
the next mutation. Declare `ETag` on every response that emits it, including
the 204 image-delete response, and assert those response headers in the
OpenAPI test.

### 4. The generated update operation omits one accepted HTTP method

The route uses Laravel's `apiResource`, which accepts both `PUT` and `PATCH`
for update ([`routes/api.php:22-23`](../../routes/api.php#L22-L23)); the
controller supplies the common update action
([`app/Http/Controllers/VehicleController.php:71-103`](../../app/Http/Controllers/VehicleController.php#L71-L103)).
The generated `/docs/api.json` inspected from this checkout contained `PUT`
but not `PATCH` at `/vehicles/{vehicle}`. The OpenAPI test checks the path, not
the method set ([`tests/Feature/OpenApiDocumentationTest.php:12-28`](../../tests/Feature/OpenApiDocumentationTest.php#L12-L28)).

**Impact:** generated clients may reject a valid `PATCH` request. Document both
methods, or intentionally narrow the public route, and add a method assertion.

### 5. The image-list route's rate-limit contract is likely stale relative to its sibling routes

All vehicle routes are wrapped in `throttle:api`
([`routes/api.php:22-29`](../../routes/api.php#L22-L29)). Other vehicle
controllers explicitly annotate a 429 response, including the catalog
([`app/Http/Controllers/VehicleController.php:26-28`](../../app/Http/Controllers/VehicleController.php#L26-L28)),
but `VehicleImageIndexController` has no 429 annotation
([`app/Http/Controllers/VehicleImageIndexController.php:11-22`](../../app/Http/Controllers/VehicleImageIndexController.php#L11-L22)).
The generated-document test asserts 429 only for `GET /vehicles`
([`tests/Feature/OpenApiDocumentationTest.php:47-70`](../../tests/Feature/OpenApiDocumentationTest.php#L47-L70)).

**Impact:** `GET /vehicles/{vehicle}/images` is governed by the same middleware
but has no source-level documentation declaration or regression assertion for
429. Annotate it consistently and add a path-specific assertion.

### 6. Upload documentation misses the whole-gallery capacity rule and replay semantics

The upload endpoint description and request rules accurately state 1–10 files,
formats, and 2 MiB per file ([`app/Http/Controllers/UploadVehicleImagesController.php:18-39`](../../app/Http/Controllers/UploadVehicleImagesController.php#L18-L39),
[`app/Http/Requests/UploadVehicleImagesRequest.php:14-17`](../../app/Http/Requests/UploadVehicleImagesRequest.php#L14-L17)).
At runtime, the lifecycle also rejects a batch when the existing gallery plus
the upload exceeds 20 images ([`app/Domain/Vehicles/VehicleGallery/VehicleImageLifecycle.php:43-56`](../../app/Domain/Vehicles/VehicleGallery/VehicleImageLifecycle.php#L43-L56));
the behaviour is tested ([`tests/Feature/VehicleReliabilityTest.php:63-81`](../../tests/Feature/VehicleReliabilityTest.php#L63-L81)).
Separately, a completed upload is replayed with its stored status, body, and
ETag before a current-version check ([`app/Http/Controllers/UploadVehicleImagesController.php:56-77`](../../app/Http/Controllers/UploadVehicleImagesController.php#L56-L77)),
and that replay is tested ([`tests/Feature/VehicleReliabilityTest.php:42-61`](../../tests/Feature/VehicleReliabilityTest.php#L42-L61)).
The OpenAPI test checks the per-request constraints only
([`tests/Feature/OpenApiDocumentationTest.php:47-69`](../../tests/Feature/OpenApiDocumentationTest.php#L47-L69)).

**Impact:** a consumer can follow the documented 10-file limit and still receive
a validation failure because of the undocumented 20-image gallery limit; it
also has no documented expectation that retries with an identical
`Idempotency-Key` replay the original successful response. Add both behaviours
to the operation description/responses and protect them with document tests.

The generated upload operation additionally contains a generic `200` response,
although the controller's ordinary success is `201` and a completed replay
uses its saved original status
([`app/Http/Controllers/UploadVehicleImagesController.php:56-77`](../../app/Http/Controllers/UploadVehicleImagesController.php#L56-L77)).
It omits the duplicate-key `409` cases
([`app/Domain/Vehicles/VehicleUploadReplay.php:65-71`](../../app/Domain/Vehicles/VehicleUploadReplay.php#L65-L71))
and the shared `412`/`428` version outcomes. Further, its header metadata says
only `string` ([`app/Http/Controllers/UploadVehicleImagesController.php:33-38`](../../app/Http/Controllers/UploadVehicleImagesController.php#L33-L38)),
whereas runtime requires a UUID
([`app/Http/Controllers/UploadVehicleImagesController.php:47-53`](../../app/Http/Controllers/UploadVehicleImagesController.php#L47-L53)).

**Impact:** clients lack a complete retry/error protocol and may send a
non-UUID key that the schema accepts but the endpoint rejects. Declare the
responses and UUID format, and assert them in the generated document.

## What is currently synchronized

The committed OpenAPI test does protect route presence, cookie-based Sanctum
security, CSRF header naming for vehicle creation, pagination envelope fields,
selected resource types/enums, upload multipart constraints, catalog sort help,
and the catalog's 429 response ([`tests/Feature/OpenApiDocumentationTest.php:12-70`](../../tests/Feature/OpenApiDocumentationTest.php#L12-L70)).
CI runs Scramble analysis ([`.github/workflows/tests.yml:40-44`](../../.github/workflows/tests.yml#L40-L44)),
so malformed generated output is caught; the gaps above are semantic coverage
gaps rather than evidence that generation is disabled.
