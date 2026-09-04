<?php

namespace App\Http\Controllers;

use App\Actions\DeleteVehicleImage;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Http\Request;

class DeleteVehicleImageController extends Controller
{
    public function __invoke(Request $request, Vehicle $vehicle, VehicleImage $image, DeleteVehicleImage $action)
    {
        $this->authorize('manageImages', $vehicle);
        $action->execute($vehicle, $image, $request->user());

        return response()->noContent();
    }
}
