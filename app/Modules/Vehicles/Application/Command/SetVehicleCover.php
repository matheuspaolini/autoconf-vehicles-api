<?php

namespace App\Modules\Vehicles\Application\Command;

use App\Modules\Vehicles\Domain\Model\Actor;

final readonly class SetVehicleCover
{
    public function __construct(
        public int $vehicleId,
        public int $imageId,
        public int $expectedVersion,
        public Actor $actor,
    ) {}
}
