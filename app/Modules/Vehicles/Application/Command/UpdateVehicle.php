<?php

namespace App\Modules\Vehicles\Application\Command;

use App\Modules\Vehicles\Application\Data\VehicleDetailsData;
use App\Modules\Vehicles\Domain\Model\Actor;

final readonly class UpdateVehicle
{
    public function __construct(
        public int $vehicleId,
        public int $expectedVersion,
        public Actor $actor,
        public VehicleDetailsData $details,
    ) {}
}
