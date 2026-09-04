<?php

namespace App\Actions;

use App\Domain\Vehicles\VehicleMediaRetirement;
use App\Domain\Vehicles\VehicleVersion;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

final class DeleteVehicle
{
    public function __construct(
        private readonly VehicleMediaRetirement $mediaRetirement,
    ) {
    }

    public function execute(Vehicle $vehicle, int $expectedVersion): void
    {
        DB::transaction(function () use ($vehicle, $expectedVersion): void {
            $lockedVehicle = Vehicle::query()
                ->whereKey($vehicle->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            app(VehicleVersion::class)->assertCurrent($lockedVehicle, $expectedVersion);

            $paths = $lockedVehicle->images()->orderBy('id')->pluck('path')->all();
            $this->mediaRetirement->retire(paths: $paths, directory: "vehicles/{$lockedVehicle->getKey()}");
            $lockedVehicle->delete();
        });
    }
}
