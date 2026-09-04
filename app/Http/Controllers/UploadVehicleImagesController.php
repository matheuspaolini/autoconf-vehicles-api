<?php

namespace App\Http\Controllers;

use App\Domain\Vehicles\VehicleGallery\VehicleImageLifecycle;
use App\Domain\Vehicles\VehicleUploadReplay;
use App\Domain\Vehicles\VehicleVersion;
use App\Http\Requests\UploadVehicleImagesRequest;
use App\Http\Resources\VehicleImageResource;
use App\Models\Vehicle;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Header;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

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
        Vehicle $vehicle,
        VehicleImageLifecycle $lifecycle,
        VehicleUploadReplay $replay,
        VehicleVersion $version,
    ) {
        $key = \trim((string) $request->header('Idempotency-Key'));

        if (! Str::isUuid($key)) {
            throw ValidationException::withMessages([
                'Idempotency-Key' => ['The Idempotency-Key header must be a UUID.'],
            ]);
        }

        $files = $request->file('files');
        $claim = $replay->claim($request->user(), $vehicle, $key, $replay->fingerprint($files));

        if ($claim->status === 'completed') {
            return response()->json($claim->response_body, $claim->response_status)
                ->header('ETag', $claim->response_etag);
        }

        try {
            /** @var array<string, mixed> $body */
            $body = [];
            $etag = '';

            $lifecycle->upload(
                $vehicle,
                $request->user(),
                $files,
                $version->expectedVersion($request, $vehicle),
                function (Collection $images, Vehicle $lockedVehicle) use (&$body, &$etag, $claim, $replay, $version): void {
                    $etag = $version->etag($lockedVehicle);
                    /** @var array<string, mixed> $body */
                    $body = VehicleImageResource::collection($images)->response()->getData(true);
                    $replay->complete($claim, $body, 201, $etag);
                },
            );

            return response()->json($body, 201)->header('ETag', $etag);
        } catch (Throwable $exception) {
            $replay->abandon($claim);

            throw $exception;
        }
    }
}
