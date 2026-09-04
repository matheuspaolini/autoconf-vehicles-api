<?php

namespace App\Jobs;

use App\Models\MediaCleanupTask;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class CleanupVehicleMedia implements ShouldQueue
{
    use Dispatchable, Queueable;

    private const MAX_ATTEMPTS = 3;

    /** @var list<int> */
    private const RETRY_BACKOFF_SECONDS = [5, 60, 300];

    public int $tries = self::MAX_ATTEMPTS;

    /** @var list<int> */
    public array $backoff = self::RETRY_BACKOFF_SECONDS;

    public function __construct(public readonly int $taskId) {}

    public function handle(): void
    {
        $task = MediaCleanupTask::query()->find($this->taskId);

        if (! $task || $task->completed_at) {
            return;
        }

        try {
            $disk = Storage::disk('public');

            foreach ($task->paths as $path) {
                if (! $disk->delete($path)) {
                    throw new RuntimeException("Unable to delete media file: {$path}");
                }
            }

            if ($task->directory && ! $disk->deleteDirectory($task->directory)) {
                throw new RuntimeException("Unable to delete media directory: {$task->directory}");
            }

            $task->forceFill([
                'attempts' => $task->attempts + 1,
                'last_error' => null,
                'completed_at' => now(),
            ])->save();

            Log::info('Vehicle media cleanup completed', [
                'cleanup_task_id' => $task->getKey(),
                'paths' => $task->paths,
                'directory' => $task->directory,
            ]);
        } catch (Throwable $exception) {
            $task->forceFill([
                'attempts' => $task->attempts + 1,
                'last_error' => $exception->getMessage(),
            ])->save();

            Log::warning('Vehicle media cleanup failed', [
                'cleanup_task_id' => $task->getKey(),
                'paths' => $task->paths,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }
}
