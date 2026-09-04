<?php

namespace App\Actions;

use App\Domain\Vehicles\VehicleGallery\PendingVehicleGalleryCleanup;
use App\Domain\Vehicles\VehicleGallery\VehicleImageLifecycle;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

final class DeleteVehicle
{
    public function __construct(
        private readonly VehicleImageLifecycle $imageLifecycle,
    ) {}

    public function execute(Vehicle $vehicle): void
    {
        $cleanup = DB::transaction(function () use ($vehicle): PendingVehicleGalleryCleanup {
            /** @var Vehicle $lockedVehicle */
            $lockedVehicle = Vehicle::query()
                ->whereKey($vehicle->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $cleanup = $this->imageLifecycle->prepareVehicleDeletion($lockedVehicle);
            $lockedVehicle->delete();

            return $cleanup;
        });

        $cleanup->execute();
    }
}
