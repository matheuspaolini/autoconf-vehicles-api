<?php

namespace App\Modules\Vehicles\Application\Port;

use App\Modules\Vehicles\Domain\Model\Vehicle;
use App\Modules\Vehicles\Domain\ValueObject\VehicleId;

interface VehicleWriteGateway
{
    public function nextIdentity(): VehicleId;

    public function getForUpdate(VehicleId $id): ?Vehicle;

    public function insert(Vehicle $vehicle, int $createdBy): void;

    public function update(Vehicle $vehicle): void;

    public function delete(Vehicle $vehicle): void;
}
