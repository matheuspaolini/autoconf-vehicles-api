<?php

namespace App\Actions;

use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeleteVehicle
{
    public function execute(Vehicle $vehicle): void
    {
        $id = $vehicle->id;
        $paths = DB::transaction(function () use ($vehicle): array {
            $locked = Vehicle::query()->with('images')->whereKey($vehicle)->lockForUpdate()->firstOrFail();
            $paths = $locked->images->pluck('path')->all();
            $locked->delete();

            return $paths;
        });
        foreach ($paths as $path) {
            if (! Storage::disk('public')->delete($path)) {
                Log::warning('Vehicle image file could not be deleted', ['vehicle_id' => $id, 'path' => $path]);
            }
        }
        if (! Storage::disk('public')->deleteDirectory("vehicles/{$id}")) {
            Log::warning('Vehicle image directory could not be deleted', ['vehicle_id' => $id]);
        }
    }
}
