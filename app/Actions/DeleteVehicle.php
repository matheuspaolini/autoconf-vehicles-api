<?php

namespace App\Actions;

use App\Domain\Vehicles\VehicleVersion;
use App\Jobs\CleanupVehicleMedia;
use App\Models\MediaCleanupTask;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

final class DeleteVehicle
{
    public function execute(Vehicle $vehicle, int $expectedVersion): void
    {
        $taskId = DB::transaction(function () use ($vehicle, $expectedVersion): int {
            /** @var Vehicle $lockedVehicle */
            $lockedVehicle = Vehicle::query()
                ->whereKey($vehicle->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            app(VehicleVersion::class)->assertCurrent($lockedVehicle, $expectedVersion);

            $paths = $lockedVehicle->images()->orderBy('id')->pluck('path')->all();
            $task = MediaCleanupTask::query()->create([
                'paths' => $paths,
                'directory' => "vehicles/{$lockedVehicle->getKey()}",
                'last_dispatched_at' => now(),
            ]);
            $lockedVehicle->delete();

            return $task->getKey();
        });

        CleanupVehicleMedia::dispatch($taskId);
    }
}
