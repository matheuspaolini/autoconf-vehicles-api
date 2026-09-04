<?php

namespace App\Http\Controllers;

use App\Actions\DeleteVehicle;
use App\Domain\Vehicles\Read\VehicleCatalogGrammar;
use App\Domain\Vehicles\Read\VehicleRead;
use App\Domain\Vehicles\VehicleVersion;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Http\Requests\VehicleIndexRequest;
use App\Http\Resources\VehicleDetailResource;
use App\Http\Resources\VehicleListResource;
use App\Models\User;
use App\Models\Vehicle;
use Dedoc\Scramble\Attributes\Header;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{
    /** @response AnonymousResourceCollection<LengthAwarePaginator<int, VehicleListResource>> */
    #[Response(429, 'Too many API requests.')]
    public function index(
        VehicleIndexRequest $request,
        VehicleCatalogGrammar $catalogGrammar,
        VehicleRead $vehicleRead,
    ): AnonymousResourceCollection {
        /** @var User $actor */
        $actor = $request->user();

        return VehicleListResource::collection($vehicleRead->catalog($catalogGrammar->criteria($request->validated()), $actor));
    }

    #[HeaderParameter(
        'X-XSRF-TOKEN',
        'Value from the XSRF-TOKEN cookie issued by GET /sanctum/csrf-cookie.',
        true,
        type: 'string',
    )]
    #[Response(429, 'Too many API requests.')]
    #[Header('ETag', 'Strong Vehicle version for a subsequent existing-Vehicle mutation.', type: 'string', required: true, status: 201)]
    public function store(StoreVehicleRequest $request, VehicleRead $vehicleRead, VehicleVersion $version): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $vehicle = new Vehicle($request->validated());
        $vehicle->user_id = $actor->id;
        $vehicle->created_by = $actor->id;
        $vehicle->updated_by = $actor->id;
        $vehicle->save();

        $detail = $vehicleRead->detail((int) $vehicle->getKey());

        return VehicleDetailResource::make($detail)->response()->setStatusCode(201)->header('ETag', $version->etag($detail));
    }

    #[Response(429, 'Too many API requests.')]
    #[Header('ETag', 'Strong Vehicle version for a subsequent existing-Vehicle mutation.', type: 'string', required: true, status: 200)]
    public function show(Request $request, Vehicle $vehicle, VehicleRead $vehicleRead, VehicleVersion $version): JsonResponse
    {
        $this->authorize('view', $vehicle);

        $detail = $vehicleRead->detail((int) $vehicle->getKey());

        return VehicleDetailResource::make($detail)->response()->header('ETag', $version->etag($detail));
    }

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
    #[Response(412, 'The Vehicle version is malformed or no longer current.')]
    #[Response(428, 'The If-Match header is required.')]
    #[Response(429, 'Too many API requests.')]
    #[Header('ETag', 'Strong Vehicle version for a subsequent existing-Vehicle mutation.', type: 'string', required: true, status: 200)]
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle, VehicleRead $vehicleRead, VehicleVersion $version): JsonResponse
    {
        $expectedVersion = $version->expectedVersion($request, $vehicle);
        $updated = Vehicle::query()
            ->whereKey($vehicle->getKey())
            ->where('lock_version', $expectedVersion)
            ->update([
                ...$request->validated(),
                'updated_by' => $request->user()->getKey(),
                'updated_at' => now(),
                'lock_version' => DB::raw('lock_version + 1'),
            ]);

        if ($updated !== 1) {
            abort(412, 'The Vehicle version is no longer current.');
        }

        $detail = $vehicleRead->detail((int) $vehicle->getKey());

        return VehicleDetailResource::make($detail)->response()->header('ETag', $version->etag($detail));
    }

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
    #[Response(412, 'The Vehicle version is malformed or no longer current.')]
    #[Response(428, 'The If-Match header is required.')]
    #[Response(429, 'Too many API requests.')]
    #[Header('ETag', 'Strong Vehicle version after the deletion.', type: 'string', required: true, status: 204)]
    public function destroy(Request $request, Vehicle $vehicle, DeleteVehicle $action, VehicleVersion $version)
    {
        $this->authorize('delete', $vehicle);
        $action->execute($vehicle, $version->expectedVersion($request, $vehicle));

        return response()->noContent();
    }
}
