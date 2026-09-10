<?php

namespace App\Modules\Vehicles\Application\Query;

use App\Modules\Vehicles\Application\Data\Pagination;
use App\Modules\Vehicles\Application\Data\VehicleImagePageData;
use App\Modules\Vehicles\Application\Port\VehicleReadGateway;
use App\Modules\Vehicles\Domain\ValueObject\VehicleId;

final readonly class ListVehicleImagesHandler
{
    public function __construct(private VehicleReadGateway $vehicles) {}

    public function handle(int $vehicleId, Pagination $pagination): ?VehicleImagePageData
    {
        return $this->vehicles->gallery(new VehicleId($vehicleId), $pagination);
    }
}
