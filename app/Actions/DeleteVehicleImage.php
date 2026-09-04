<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeleteVehicleImage
{
    public function execute(Vehicle $vehicle, VehicleImage $image, User $actor): void
    {
        $path = DB::transaction(function () use ($vehicle, $image, $actor): string {
            $locked = Vehicle::query()->whereKey($vehicle)->lockForUpdate()->firstOrFail();
            $images = $locked->images()->lockForUpdate()->orderBy('id')->get();
            $target = $images->firstWhere('id', $image->id) ?? abort(404);
            $wasCover = $target->is_cover;
            $path = $target->path;
            $target->delete();
            if ($wasCover && ($replacement = $images->where('id', '!=', $image->id)->first())) {
                $replacement->forceFill(['is_cover' => true])->save();
            }
            $locked->forceFill(['updated_by' => $actor->id])->touch();

            return $path;
        });
        if (! Storage::disk('public')->delete($path)) {
            Log::warning('Vehicle image file could not be deleted', ['vehicle_id' => $vehicle->id, 'image_id' => $image->id, 'path' => $path]);
        }
    }
}
