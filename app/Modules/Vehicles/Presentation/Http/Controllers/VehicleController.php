<?php

namespace App\Modules\Vehicles\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Vehicles\Application\Command\CreateVehicle;
use App\Modules\Vehicles\Application\Command\CreateVehicleHandler;
use App\Modules\Vehicles\Application\Command\DeleteVehicle as DeleteVehicleCommand;
use App\Modules\Vehicles\Application\Command\DeleteVehicleHandler;
use App\Modules\Vehicles\Application\Command\UpdateVehicle as UpdateVehicleCommand;
use App\Modules\Vehicles\Application\Command\UpdateVehicleHandler;
use App\Modules\Vehicles\Application\Data\VehicleDetailsData;
use App\Modules\Vehicles\Application\Query\GetVehicleDetailHandler;
use App\Modules\Vehicles\Application\Query\ListVehiclesHandler;
use App\Modules\Vehicles\Presentation\Http\Requests\StoreVehicleRequest;
use App\Modules\Vehicles\Presentation\Http\Requests\UpdateVehicleRequest;
use App\Modules\Vehicles\Presentation\Http\Requests\VehicleIndexRequest;
use App\Modules\Vehicles\Presentation\Http\Resources\VehicleDetailResource;
use App\Modules\Vehicles\Presentation\Http\Resources\VehicleListResource;
use App\Modules\Vehicles\Presentation\Http\Support\ActorFactory;
use App\Modules\Vehicles\Presentation\Http\Support\PageDataPaginator;
use App\Modules\Vehicles\Presentation\Http\Support\VehicleCommandResponse;
use App\Modules\Vehicles\Presentation\Http\Support\VehicleEtag;
use Dedoc\Scramble\Attributes\Header;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class VehicleController extends Controller
{
    /** @response AnonymousResourceCollection<LengthAwarePaginator<int, VehicleListResource>> */
    #[Response(429, 'Too many API requests.')]
    public function index(
        VehicleIndexRequest $request,
        ListVehiclesHandler $handler,
        PageDataPaginator $paginator,
    ): AnonymousResourceCollection {
        return VehicleListResource::collection($paginator->make($handler->handle($request->criteria())))->preserveQuery();
    }

    #[HeaderParameter(
        'X-XSRF-TOKEN',
        'Value from the XSRF-TOKEN cookie issued by GET /sanctum/csrf-cookie.',
        true,
        type: 'string',
    )]
    #[Response(201, 'Vehicle created successfully.')]
    #[Response(429, 'Too many API requests.')]
    #[Header('ETag', 'Strong Vehicle version for a subsequent existing-Vehicle mutation.', type: 'string', required: true, status: 201)]
    public function store(
        StoreVehicleRequest $request,
        CreateVehicleHandler $handler,
        ActorFactory $actors,
        VehicleCommandResponse $responses,
    ): JsonResponse {
        $input = $request->validated();
        /** @var User $actor */
        $actor = $request->user();
        $command = new CreateVehicle(
            actor: $actors->fromUser($actor),
            details: new VehicleDetailsData(
                plate: $input['placa'],
                chassis: $input['chassi'],
                brand: $input['marca'],
                model: $input['modelo'],
                trim: $input['versao'],
                salePrice: (string) $input['valor_venda'],
                color: $input['cor'],
                mileage: (int) $input['km'],
                transmission: $input['cambio'],
                fuelType: $input['combustivel'],
            ),
        );

        return $responses->created($handler->handle($command));
    }

    #[Response(429, 'Too many API requests.')]
    #[Header('ETag', 'Strong Vehicle version for a subsequent existing-Vehicle mutation.', type: 'string', required: true, status: 200)]
    public function show(Request $request, int $vehicle, GetVehicleDetailHandler $handler, VehicleEtag $etag): JsonResponse
    {
        $detail = $handler->handle($vehicle);
        abort_if($detail === null, 404);

        return VehicleDetailResource::make($detail)->response()->header('ETag', $etag->format($detail->id, $detail->version));
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
    public function update(
        UpdateVehicleRequest $request,
        int $vehicle,
        UpdateVehicleHandler $handler,
        ActorFactory $actors,
        VehicleEtag $etag,
        VehicleCommandResponse $responses,
    ): JsonResponse {
        $input = $request->validated();
        /** @var User $actor */
        $actor = $request->user();
        $command = new UpdateVehicleCommand(
            vehicleId: $vehicle,
            expectedVersion: $etag->expectedVersion($request, $vehicle),
            actor: $actors->fromUser($actor),
            details: new VehicleDetailsData(
                plate: $input['placa'],
                chassis: $input['chassi'],
                brand: $input['marca'],
                model: $input['modelo'],
                trim: $input['versao'],
                salePrice: (string) $input['valor_venda'],
                color: $input['cor'],
                mileage: (int) $input['km'],
                transmission: $input['cambio'],
                fuelType: $input['combustivel'],
            ),
        );

        return $responses->updated($handler->handle($command));
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
    #[Response(204, 'Vehicle deleted successfully.')]
    #[Header('ETag', 'Strong Vehicle version after the deletion.', type: 'string', required: true, status: 204)]
    public function destroy(
        Request $request,
        int $vehicle,
        DeleteVehicleHandler $handler,
        ActorFactory $actors,
        VehicleEtag $etag,
        VehicleCommandResponse $responses,
    ): HttpResponse {
        /** @var User $actor */
        $actor = $request->user();
        $command = new DeleteVehicleCommand(
            vehicleId: $vehicle,
            expectedVersion: $etag->expectedVersion($request, $vehicle),
            actor: $actors->fromUser($actor),
        );

        return $responses->deletedVehicle($handler->handle($command))
            ->header('ETag', $etag->format($vehicle, $command->expectedVersion + 1));
    }
}
