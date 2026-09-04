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
            $locked = Vehicle::query()->whereKey($vehicle)->lockForUpdate()->firstOrFail();
            $locked->images()->update(['is_cover' => false]);
            $image->forceFill(['is_cover' => true])->save();
            $locked->forceFill(['updated_by' => $actor->id])->touch();

            return $image->refresh();
        });
    }
}
