<?php

namespace App\Modules\Vehicles\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Vehicles\Application\Command\UploadVehicleImages;
use App\Modules\Vehicles\Application\Command\UploadVehicleImagesHandler;
use App\Modules\Vehicles\Application\Result\UploadVehicleImagesError;
use App\Modules\Vehicles\Presentation\Http\Requests\UploadVehicleImagesRequest;
use App\Modules\Vehicles\Presentation\Http\Support\ActorFactory;
use App\Modules\Vehicles\Presentation\Http\Support\LaravelUploadSource;
use App\Modules\Vehicles\Presentation\Http\Support\VehicleEtag;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Header;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UploadVehicleImagesController extends Controller
{
    #[Endpoint(description: 'Upload between one and ten JPEG, PNG, or WebP files. Each file must be at most 2 MiB, and a Vehicle Gallery can contain at most 20 images. Reuse an Idempotency-Key only to retry the identical batch for up to 24 hours; a completed retry returns the original response without duplicating files or image rows.')]
    #[HeaderParameter(
        'X-XSRF-TOKEN',
        'Value from the XSRF-TOKEN cookie issued by GET /sanctum/csrf-cookie.',
        true,
        type: 'string',
    )]
    #[HeaderParameter(
        'If-Match',
        'The current Vehicle ETag returned by a single-Vehicle read.',
        true,
        type: 'string',
    )]
    #[HeaderParameter(
        'Idempotency-Key',
        'A UUID that remains unchanged while retrying the identical upload batch.',
        true,
        type: 'string',
        format: 'uuid',
    )]
    #[Response(201, 'Images uploaded successfully.')]
    #[Response(409, 'The Idempotency-Key was used for a different batch or that batch is still processing.')]
    #[Response(412, 'The Vehicle version is malformed or no longer current.')]
    #[Response(422, 'The upload is invalid or would exceed the 20-image Vehicle Gallery limit.')]
    #[Response(428, 'The If-Match header is required.')]
    #[Response(429, 'Too many API or upload requests.')]
    #[Header('ETag', 'Strong Vehicle version for a subsequent existing-Vehicle mutation.', type: 'string', required: true, status: 201)]
    public function __invoke(
        UploadVehicleImagesRequest $request,
        int $vehicle,
        UploadVehicleImagesHandler $handler,
        ActorFactory $actors,
        VehicleEtag $etag,
    ): JsonResponse {
        $key = \trim((string) $request->header('Idempotency-Key'));

        if (! Str::isUuid($key)) {
            throw ValidationException::withMessages([
                'Idempotency-Key' => ['The Idempotency-Key header must be a UUID.'],
            ]);
        }

        $parsedEtag = $etag->parse($request, $vehicle);
        /** @var User $actor */
        $actor = $request->user();
        $files = \array_map(
            static fn ($file): LaravelUploadSource => new LaravelUploadSource($file),
            $request->file('files'),
        );
        $result = $handler->handle(new UploadVehicleImages(
            vehicleId: $vehicle,
            expectedVersion: $parsedEtag->version,
            versionHeaderMissing: $parsedEtag->missing,
            idempotencyKey: $key,
            actor: $actors->fromUser($actor),
            files: $files,
        ));

        if ($result->response !== null) {
            return response()->json($result->response->body, $result->response->status)
                ->header('ETag', $result->response->etag);
        }

        match ($result->error) {
            UploadVehicleImagesError::IdempotencyConflict => throw new HttpException(
                409,
                'This Idempotency-Key was already used with a different upload.',
            ),
            UploadVehicleImagesError::UploadProcessing => throw new HttpException(
                409,
                'This upload is already processing.',
                null,
                ['Retry-After' => '5'],
            ),
            UploadVehicleImagesError::MissingVersion => throw new HttpException(428, 'The If-Match header is required.'),
            UploadVehicleImagesError::VersionConflict => throw new HttpException(412, 'The Vehicle version is no longer current.'),
            UploadVehicleImagesError::GalleryCapacityExceeded => throw ValidationException::withMessages([
                'files' => ['A Vehicle Gallery can contain at most 20 images.'],
            ]),
            UploadVehicleImagesError::NotFound => abort(404),
            UploadVehicleImagesError::Forbidden => abort(403),
            default => throw new \LogicException('Unexpected upload result.'),
        };
    }
}
