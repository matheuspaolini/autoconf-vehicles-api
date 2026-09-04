<?php

namespace App\Http\Controllers;

use App\Actions\DeleteVehicleImage;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;

class DeleteVehicleImageController extends Controller
{
    #[HeaderParameter(
        'X-XSRF-TOKEN',
        'Value from the XSRF-TOKEN cookie issued by GET /sanctum/csrf-cookie.',
        true,
        type: 'string',
    )]
    #[Response(429, 'Too many API requests.')]
    public function __invoke(Request $request, Vehicle $vehicle, VehicleImage $image, DeleteVehicleImage $action)
    {
        $this->authorize('manageImages', $vehicle);
        $action->execute($vehicle, $image, $request->user());

        return response()->noContent();
    }
}
