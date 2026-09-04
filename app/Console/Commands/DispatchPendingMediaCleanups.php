<?php

namespace App\Console\Commands;

use App\Jobs\CleanupVehicleMedia;
use App\Models\MediaCleanupTask;
use App\Models\VehicleUploadRequest;
use Illuminate\Console\Command;

class DispatchPendingMediaCleanups extends Command
{
    protected $signature = 'media:reconcile-cleanup';

    protected $description = 'Dispatch unfinished Vehicle media cleanup tasks.';

    public function handle(): int
    {
        $cutoff = now()->subMinutes(15);

        MediaCleanupTask::query()
            ->whereNull('completed_at')
            ->where(fn ($query) => $query->whereNull('last_dispatched_at')->orWhere('last_dispatched_at', '<=', $cutoff))
            ->orderBy('id')
            ->lazyById(100)
            ->each(function (MediaCleanupTask $task): void {
                $task->forceFill(['last_dispatched_at' => now()])->save();
                CleanupVehicleMedia::dispatch($task->getKey());
            });

        VehicleUploadRequest::query()->where('expires_at', '<=', now())->delete();

        return self::SUCCESS;
    }
}
