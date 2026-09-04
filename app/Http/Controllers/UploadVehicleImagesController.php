<?php

namespace App\Http\Controllers;

use App\Domain\Vehicles\VehicleGallery\VehicleImageLifecycle;
use App\Domain\Vehicles\VehicleUploadReplay;
use App\Domain\Vehicles\VehicleVersion;
use App\Http\Requests\UploadVehicleImagesRequest;
use App\Http\Resources\VehicleImageResource;
use App\Models\Vehicle;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class UploadVehicleImagesController extends Controller
{
    #[Endpoint(description: 'Upload between one and ten JPEG, PNG, or WebP files. Each file must be at most 2 MiB.')]
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
    )]
    #[Response(429, 'Too many API or upload requests.')]
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
            $images = $lifecycle->upload(
                $vehicle,
                $request->user(),
                $files,
                $version->expectedVersion($request, $vehicle),
            );
            $currentVehicle = Vehicle::query()->findOrFail($vehicle->getKey());
            $etag = $version->etag($currentVehicle);
            $response = VehicleImageResource::collection($images)->response()->setStatusCode(201)->header('ETag', $etag);
            /** @var array<string, mixed> $body */
            $body = $response->getData(true);
            $replay->complete($claim, $body, 201, $etag);

            return $response;
        } catch (Throwable $exception) {
            $replay->abandon($claim);

            throw $exception;
        }
    }
}
