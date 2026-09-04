<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Support\Facades\DB;

class SetVehicleCover
{
    public function execute(Vehicle $vehicle, VehicleImage $image, User $actor): VehicleImage
    {
        return DB::transaction(function () use ($vehicle, $image, $actor): VehicleImage {
            /** @var Vehicle $lockedVehicle */
            $lockedVehicle = Vehicle::query()
                ->whereKey($vehicle->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            /** @var VehicleImage $targetImage */
            $targetImage = $lockedVehicle->images()
                ->whereKey($image->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedVehicle->images()->update(['is_cover' => false]);
            $targetImage->forceFill(['is_cover' => true])->save();
            $lockedVehicle->forceFill(['updated_by' => $actor->id])->touch();

            return $targetImage->refresh();
        });
    }
}
