<?php

namespace App\Http\Controllers;

use App\Domain\Vehicles\Read\VehicleRepresentationRead;
use App\Domain\Vehicles\VehicleGallery\VehicleImageLifecycle;
use App\Domain\Vehicles\VehicleVersion;
use App\Http\Resources\VehicleImageResource;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;

class SetVehicleCoverController extends Controller
{
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
    #[Response(429, 'Too many API requests.')]
    public function __invoke(Request $request, Vehicle $vehicle, VehicleImage $image, VehicleImageLifecycle $lifecycle, VehicleRepresentationRead $vehicleRead, VehicleVersion $version)
    {
        $this->authorize('manageImages', $vehicle);

        $result = $lifecycle->setCover($vehicle, $image, $request->user(), $version->expectedVersion($request, $vehicle));
        $currentVehicle = Vehicle::query()->findOrFail($vehicle->getKey());

        return VehicleImageResource::make($vehicleRead->image($result))->response()->header('ETag', $version->etag($currentVehicle));
    }
}
