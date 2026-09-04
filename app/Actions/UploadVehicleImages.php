<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UploadVehicleImages
{
    /** @param array<UploadedFile> $files @return Collection<int, VehicleImage> */
    public function execute(Vehicle $vehicle, User $actor, array $files): Collection
    {
        $paths = [];
        try {
            foreach ($files as $file) {
                $paths[] = $file->store("vehicles/{$vehicle->getKey()}", 'public');
            }

            return DB::transaction(function () use ($vehicle, $actor, $paths): Collection {
                $locked = Vehicle::query()->whereKey($vehicle)->lockForUpdate()->firstOrFail();
                $first = ! $locked->images()->exists();
                $created = new Collection;
                foreach ($paths as $index => $path) {
                    $created->push($locked->images()->create(['path' => $path, 'is_cover' => $first && $index === 0]));
                }
                $locked->forceFill(['updated_by' => $actor->id])->touch();

                return $created;
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($paths);
            throw $exception;
        }
    }
}
