<?php

namespace App\Modules\Vehicles\Application\Command;

use App\Modules\Vehicles\Application\Data\VehicleDetailsData;
use App\Modules\Vehicles\Domain\Model\Actor;

final readonly class CreateVehicle
{
    public function __construct(
        public Actor $actor,
        public VehicleDetailsData $details,
    ) {}
}
