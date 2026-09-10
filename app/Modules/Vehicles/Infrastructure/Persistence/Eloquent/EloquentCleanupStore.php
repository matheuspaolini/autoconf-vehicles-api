<?php

namespace App\Modules\Vehicles\Infrastructure\Persistence\Eloquent;

use App\Modules\Vehicles\Application\Port\CleanupStore;
use App\Modules\Vehicles\Infrastructure\Queue\CleanupVehicleMedia;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class EloquentCleanupStore implements CleanupStore
{
    public function overdueTaskIds(DateTimeImmutable $cutoff, int $limit): array
    {
        return MediaCleanupTaskRecord::query()
            ->whereNull('completed_at')
            ->where(static fn ($query) => $query
                ->whereNull('last_dispatched_at')
                ->orWhere('last_dispatched_at', '<=', $cutoff))
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    public function dispatch(int $taskId): void
    {
        MediaCleanupTaskRecord::query()->whereKey($taskId)->update(['last_dispatched_at' => now()]);

        DB::afterCommit(static function () use ($taskId): void {
            CleanupVehicleMedia::dispatch($taskId)->afterCommit();
        });
    }

    public function pendingTask(int $taskId): ?array
    {
        $task = MediaCleanupTaskRecord::query()->whereNull('completed_at')->find($taskId);

        return $task instanceof MediaCleanupTaskRecord
            ? ['paths' => $task->paths, 'directory' => $task->directory]
            : null;
    }

    public function complete(int $taskId): void
    {
        MediaCleanupTaskRecord::query()->whereKey($taskId)->increment('attempts', 1, [
            'last_error' => null,
            'completed_at' => now(),
        ]);
        Log::info('Vehicle media cleanup completed', ['cleanup_task_id' => $taskId]);
    }

    public function fail(int $taskId, string $message): void
    {
        MediaCleanupTaskRecord::query()->whereKey($taskId)->increment('attempts', 1, ['last_error' => $message]);
        Log::warning('Vehicle media cleanup failed', ['cleanup_task_id' => $taskId, 'message' => $message]);
    }

    public function pruneExpiredUploadRequests(DateTimeImmutable $now): void
    {
        VehicleUploadRequestRecord::query()->where('expires_at', '<=', $now)->delete();
    }

    public function removeOrphans(DateTimeImmutable $cutoff): void
    {
        $disk = Storage::disk('public');
        $referenced = VehicleImageRecord::query()->pluck('path')->flip()->all();

        foreach ($disk->allFiles('vehicles') as $path) {
            if (isset($referenced[$path]) || $disk->lastModified($path) > $cutoff->getTimestamp()) {
                continue;
            }

            if ($disk->delete($path)) {
                Log::info('Orphaned vehicle media removed', ['path' => $path]);
            }
        }
    }
}
