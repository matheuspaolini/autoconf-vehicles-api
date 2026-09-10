<?php

namespace App\Modules\Vehicles\Application\Command;

use App\Modules\Vehicles\Application\Port\CleanupStore;
use App\Modules\Vehicles\Application\Port\MediaStore;
use Throwable;

final readonly class CompleteVehicleMediaCleanupHandler
{
    public function __construct(
        private CleanupStore $cleanups,
        private MediaStore $media,
    ) {}

    public function handle(int $taskId): void
    {
        $task = $this->cleanups->pendingTask($taskId);

        if ($task === null) {
            return;
        }

        try {
            $this->media->delete($task['paths']);

            if ($task['directory'] !== null) {
                $this->media->deleteDirectory($task['directory']);
            }

            $this->cleanups->complete($taskId);
        } catch (Throwable $exception) {
            $this->cleanups->fail($taskId, $exception->getMessage());

            throw $exception;
        }
    }
}
