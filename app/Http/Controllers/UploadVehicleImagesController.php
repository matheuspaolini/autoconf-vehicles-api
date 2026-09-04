<?php

namespace App\Http\Controllers;

use App\Domain\Vehicles\VehicleGallery\VehicleImageLifecycle;
use App\Http\Requests\UploadVehicleImagesRequest;
use App\Http\Resources\VehicleImageResource;
use App\Models\Vehicle;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\Response;

class UploadVehicleImagesController extends Controller
{
    #[Endpoint(description: 'Upload between one and ten JPEG, PNG, or WebP files. Each file must be at most 2 MiB.')]
    #[HeaderParameter(
        'X-XSRF-TOKEN',
        'Value from the XSRF-TOKEN cookie issued by GET /sanctum/csrf-cookie.',
        true,
        type: 'string',
    )]
    #[Response(429, 'Too many API or upload requests.')]
    public function __invoke(UploadVehicleImagesRequest $request, Vehicle $vehicle, VehicleImageLifecycle $lifecycle)
    {
        return VehicleImageResource::collection($lifecycle->upload($vehicle, $request->user(), $request->file('files')))->response()->setStatusCode(201);
    }
}
