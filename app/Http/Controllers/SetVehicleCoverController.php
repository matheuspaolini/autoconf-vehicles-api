<?php

namespace App\Http\Controllers;

use App\Actions\SetVehicleCover;
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
    #[Response(429, 'Too many API requests.')]
    public function __invoke(Request $request, Vehicle $vehicle, VehicleImage $image, SetVehicleCover $action): VehicleImageResource
    {
        $this->authorize('manageImages', $vehicle);

        return VehicleImageResource::make($action->execute($vehicle, $image, $request->user()));
    }
}
