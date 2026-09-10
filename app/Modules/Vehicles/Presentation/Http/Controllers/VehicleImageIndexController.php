<?php

namespace App\Modules\Vehicles\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vehicles\Application\Data\Pagination;
use App\Modules\Vehicles\Application\Query\ListVehicleImagesHandler;
use App\Modules\Vehicles\Presentation\Http\Requests\VehicleImageIndexRequest;
use App\Modules\Vehicles\Presentation\Http\Resources\VehicleImageResource;
use App\Modules\Vehicles\Presentation\Http\Support\PageDataPaginator;
use App\Modules\Vehicles\Presentation\Http\Support\VehicleEtag;
use Dedoc\Scramble\Attributes\Header;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

class VehicleImageIndexController extends Controller
{
    #[Response(429, 'Too many API requests.')]
    #[Header('ETag', 'Strong Vehicle version for a subsequent existing-Vehicle mutation.', type: 'string', required: true, status: 200)]
    public function __invoke(
        VehicleImageIndexRequest $request,
        int $vehicle,
        ListVehicleImagesHandler $handler,
        PageDataPaginator $paginator,
        VehicleEtag $etag,
    ): JsonResponse {
        $page = $handler->handle(
            $vehicle,
            new Pagination($request->perPage(), $request->integer('page') ?: null),
        );

        abort_if($page === null, 404);

        return VehicleImageResource::collection($paginator->make($page->page))
            ->response()
            ->header('ETag', $etag->format($vehicle, $page->vehicleVersion));
    }
}
