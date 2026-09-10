<?php

namespace App\Modules\Vehicles\Presentation\Http\Resources;

use App\Modules\Vehicles\Application\Data\RenderedUploadResponse;
use App\Modules\Vehicles\Application\Port\UploadedImagesResponse;
use App\Modules\Vehicles\Presentation\Http\Support\VehicleEtag;

final readonly class LaravelUploadedImagesResponse implements UploadedImagesResponse
{
    private const CREATED = 201;

    public function __construct(private VehicleEtag $etag) {}

    public function render(int $vehicleId, int $vehicleVersion, array $images): RenderedUploadResponse
    {
        /** @var array<string, mixed> $body */
        $body = VehicleImageResource::collection($images)->response()->getData(true);

        return new RenderedUploadResponse(
            body: $body,
            status: self::CREATED,
            etag: $this->etag->format($vehicleId, $vehicleVersion),
        );
    }
}
