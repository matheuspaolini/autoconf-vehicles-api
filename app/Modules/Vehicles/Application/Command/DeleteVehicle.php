<?php

namespace App\Modules\Vehicles\Application\Command;

use App\Modules\Vehicles\Domain\Model\Actor;

final readonly class DeleteVehicle
{
    public function __construct(
        public int $vehicleId,
        public int $expectedVersion,
        public Actor $actor,
    ) {}
}
