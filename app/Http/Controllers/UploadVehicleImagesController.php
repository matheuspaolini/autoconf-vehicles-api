<?php

namespace App\Http\Controllers;

use App\Actions\UploadVehicleImages;
use App\Http\Requests\UploadVehicleImagesRequest;
use App\Http\Resources\VehicleImageResource;
use App\Models\Vehicle;

class UploadVehicleImagesController extends Controller
{
    public function __invoke(UploadVehicleImagesRequest $request, Vehicle $vehicle, UploadVehicleImages $action)
    {
        return VehicleImageResource::collection($action->execute($vehicle, $request->user(), $request->file('files')))->response()->setStatusCode(201);
    }
}
