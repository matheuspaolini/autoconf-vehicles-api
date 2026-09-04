<?php

namespace App\Http\Controllers;

use App\Domain\Vehicles\Read\VehicleRead;
use App\Domain\Vehicles\VehicleVersion;
use App\Http\Requests\VehicleImageIndexRequest;
use App\Http\Resources\VehicleImageResource;
use App\Models\Vehicle;
use Dedoc\Scramble\Attributes\Header;
use Dedoc\Scramble\Attributes\Response;

class VehicleImageIndexController extends Controller
{
    #[Response(429, 'Too many API requests.')]
    #[Header('ETag', 'Strong Vehicle version for a subsequent existing-Vehicle mutation.', type: 'string', required: true, status: 200)]
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
