<?php

namespace App\Modules\Vehicles\Application\Query;

use App\Modules\Vehicles\Application\Data\VehicleData;
use App\Modules\Vehicles\Application\Port\VehicleReadGateway;
use App\Modules\Vehicles\Domain\ValueObject\VehicleId;

final readonly class GetVehicleDetailHandler
{
    public function __construct(private VehicleReadGateway $vehicles) {}

    public function handle(int $vehicleId): ?VehicleData
    {
        return $this->vehicles->detail(new VehicleId($vehicleId));
    }
}
