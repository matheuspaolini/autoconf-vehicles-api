<?php

namespace App\Modules\Vehicles\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Vehicles\Application\Command\DeleteVehicleImage;
use App\Modules\Vehicles\Application\Command\DeleteVehicleImageHandler;
use App\Modules\Vehicles\Presentation\Http\Support\ActorFactory;
use App\Modules\Vehicles\Presentation\Http\Support\VehicleCommandResponse;
use App\Modules\Vehicles\Presentation\Http\Support\VehicleEtag;
use Dedoc\Scramble\Attributes\Header;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

class DeleteVehicleImageController extends Controller
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
    #[Response(204, 'Vehicle image deleted successfully.')]
    #[Header('ETag', 'Strong Vehicle version after the deletion.', type: 'string', required: true, status: 204)]
    public function __invoke(
        Request $request,
        int $vehicle,
        int $image,
        DeleteVehicleImageHandler $handler,
        ActorFactory $actors,
        VehicleEtag $etag,
        VehicleCommandResponse $responses,
    ): HttpResponse {
        /** @var User $actor */
        $actor = $request->user();
        $command = new DeleteVehicleImage(
            vehicleId: $vehicle,
            imageId: $image,
            expectedVersion: $etag->expectedVersion($request, $vehicle),
            actor: $actors->fromUser($actor),
        );

        $response = $responses->deletedImage($handler->handle($command));

        return $response->header('ETag', $etag->format($vehicle, $command->expectedVersion + 1));
    }
}
