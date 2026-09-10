<?php

namespace App\Modules\Vehicles\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Vehicles\Application\Command\SetVehicleCover;
use App\Modules\Vehicles\Application\Command\SetVehicleCoverHandler;
use App\Modules\Vehicles\Presentation\Http\Support\ActorFactory;
use App\Modules\Vehicles\Presentation\Http\Support\VehicleCommandResponse;
use App\Modules\Vehicles\Presentation\Http\Support\VehicleEtag;
use Dedoc\Scramble\Attributes\Header;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
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
    #[Response(412, 'The Vehicle version is malformed or no longer current.')]
    #[Response(428, 'The If-Match header is required.')]
    #[Response(429, 'Too many API requests.')]
    #[Header('ETag', 'Strong Vehicle version for a subsequent existing-Vehicle mutation.', type: 'string', required: true, status: 200)]
    public function __invoke(
        Request $request,
        int $vehicle,
        int $image,
        SetVehicleCoverHandler $handler,
        ActorFactory $actors,
        VehicleEtag $etag,
        VehicleCommandResponse $responses,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $command = new SetVehicleCover(
            vehicleId: $vehicle,
            imageId: $image,
            expectedVersion: $etag->expectedVersion($request, $vehicle),
            actor: $actors->fromUser($actor),
        );

        return $responses->image($handler->handle($command), $vehicle);
    }
}
