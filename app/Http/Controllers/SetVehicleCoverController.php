<?php

namespace App\Http\Controllers;

use App\Actions\SetVehicleCover;
use App\Http\Resources\VehicleImageResource;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Http\Request;

class SetVehicleCoverController extends Controller
{
    public function __invoke(Request $request, Vehicle $vehicle, VehicleImage $image, SetVehicleCover $action): VehicleImageResource
    {
        $this->authorize('manageImages', $vehicle);

        return VehicleImageResource::make($action->execute($vehicle, $image, $request->user()));
    }
}
