<?php

namespace App\Http\Controllers;

use App\Domain\Vehicles\VehicleVersion;
use App\Http\Requests\VehicleImageIndexRequest;
use App\Http\Resources\VehicleImageResource;
use App\Models\Vehicle;
use App\Queries\VehicleRead;

class VehicleImageIndexController extends Controller
{
    public function __invoke(
        VehicleImageIndexRequest $request,
        Vehicle $vehicle,
        VehicleRead $vehicleRead,
        VehicleVersion $version,
    ) {
        return VehicleImageResource::collection($vehicleRead->gallery($vehicle, $request->perPage()))
            ->response()
            ->header('ETag', $version->etag($vehicle));
    }
}
