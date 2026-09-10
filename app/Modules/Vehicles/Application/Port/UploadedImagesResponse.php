<?php

namespace App\Modules\Vehicles\Application\Port;

use App\Modules\Vehicles\Application\Data\RenderedUploadResponse;
use App\Modules\Vehicles\Application\Data\VehicleImageData;

interface UploadedImagesResponse
{
    /** @param list<VehicleImageData> $images */
    public function render(int $vehicleId, int $vehicleVersion, array $images): RenderedUploadResponse;
}
