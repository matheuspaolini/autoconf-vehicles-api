<?php

namespace App\Domain\Vehicles;

use App\Jobs\CleanupVehicleMedia;
use App\Models\MediaCleanupTask;
use Illuminate\Support\Facades\DB;

final class VehicleMediaRetirement
{
    public const RECONCILIATION_INTERVAL_MINUTES = 15;

    private const RECONCILIATION_BATCH_SIZE = 100;

    /** @param list<string> $paths */
    public function retire(array $paths, ?string $directory): void
    {
        $task = MediaCleanupTask::query()->create([
            'paths' => $paths,
            'directory' => $directory,
        ]);

        $this->dispatch($task);
    }

    public function reconcile(): void
    {
        $now = now();
        $reconciliationCutoff = $now->copy()->subMinutes(self::RECONCILIATION_INTERVAL_MINUTES);

        MediaCleanupTask::query()
            ->whereNull('completed_at')
            ->where(
                fn ($query) => $query
                    ->whereNull('last_dispatched_at')
                    ->orWhere('last_dispatched_at', '<=', $reconciliationCutoff),
            )
            ->orderBy('id')
            ->lazyById(self::RECONCILIATION_BATCH_SIZE)
            ->each($this->dispatch(...));
    }

    private function dispatch(MediaCleanupTask $task): void
    {
        $task->forceFill(['last_dispatched_at' => now()])->save();
        $taskId = $task->getKey();

        DB::afterCommit(static function () use ($taskId): void {
            CleanupVehicleMedia::dispatch($taskId)->afterCommit();
        });
    }
}
