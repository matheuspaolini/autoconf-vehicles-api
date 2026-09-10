<?php

namespace App\Modules\Vehicles\Infrastructure\Persistence\Eloquent;

use App\Modules\Vehicles\Application\Data\CleanupRequest;
use App\Modules\Vehicles\Application\Port\CleanupOutbox;
use App\Modules\Vehicles\Infrastructure\Queue\CleanupVehicleMedia;
use Illuminate\Support\Facades\DB;

final class EloquentCleanupOutbox implements CleanupOutbox
{
    public function record(CleanupRequest $request): int
    {
        $task = MediaCleanupTaskRecord::query()->create([
            'paths' => $request->paths,
            'directory' => $request->directory,
            'last_dispatched_at' => now(),
        ]);
        $taskId = (int) $task->getKey();

        DB::afterCommit(static function () use ($taskId): void {
            CleanupVehicleMedia::dispatch($taskId)->afterCommit();
        });

        return $taskId;
    }
}
