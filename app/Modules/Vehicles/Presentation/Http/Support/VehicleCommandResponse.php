<?php

namespace App\Modules\Vehicles\Presentation\Http\Support;

use App\Modules\Vehicles\Application\Result\CreateVehicleResult;
use App\Modules\Vehicles\Application\Result\DeleteVehicleImageResult;
use App\Modules\Vehicles\Application\Result\DeleteVehicleResult;
use App\Modules\Vehicles\Application\Result\SetVehicleCoverResult;
use App\Modules\Vehicles\Application\Result\UpdateVehicleResult;
use App\Modules\Vehicles\Presentation\Http\Resources\VehicleDetailResource;
use App\Modules\Vehicles\Presentation\Http\Resources\VehicleImageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

final readonly class VehicleCommandResponse
{
    public function __construct(private VehicleEtag $etag) {}

    public function updated(UpdateVehicleResult $result): JsonResponse
    {
        if ($result->succeeded() && $result->vehicle !== null) {
            return VehicleDetailResource::make($result->vehicle)
                ->response()
                ->header('ETag', $this->etag->format($result->vehicle->id, $result->vehicle->version));
        }

        return $this->failure($result->error?->value, $result->fieldErrors);
    }

    public function created(CreateVehicleResult $result): JsonResponse
    {
        if ($result->succeeded() && $result->vehicle !== null) {
            return VehicleDetailResource::make($result->vehicle)
                ->response()
                ->setStatusCode(201)
                ->header('ETag', $this->etag->format($result->vehicle->id, $result->vehicle->version));
        }

        return $this->failure($result->error?->value, $result->fieldErrors);
    }

    public function deletedVehicle(DeleteVehicleResult $result): Response
    {
        if ($result->succeeded()) {
            return response()->noContent();
        }

        return $this->failure($result->error?->value);
    }

    public function deletedImage(DeleteVehicleImageResult $result): Response
    {
        if ($result->succeeded()) {
            return response()->noContent();
        }

        return $this->failure($result->error?->value);
    }

    public function image(SetVehicleCoverResult $result, int $vehicleId): JsonResponse
    {
        if ($result->succeeded() && $result->image !== null && $result->vehicleVersion !== null) {
            return VehicleImageResource::make($result->image)
                ->response()
                ->header('ETag', $this->etag->format($vehicleId, $result->vehicleVersion));
        }

        return $this->failure($result->error?->value);
    }

    /** @param array<string, list<string>> $fieldErrors */
    private function failure(?string $error, array $fieldErrors = []): never
    {
        match ($error) {
            'not_found', 'image_not_found' => abort(404),
            'forbidden' => abort(403),
            'version_conflict' => abort(412, 'The Vehicle version is no longer current.'),
            'invalid_input', 'duplicate_plate', 'duplicate_chassis' => throw ValidationException::withMessages($fieldErrors),
            null => throw new \LogicException('A failed command result must contain an error.'),
            default => throw new \LogicException("Unexpected Vehicle command error [{$error}]."),
        };
    }
}
