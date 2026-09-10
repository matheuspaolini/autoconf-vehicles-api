<?php

namespace App\Modules\Vehicles\Infrastructure\Queue;

use App\Modules\Vehicles\Application\Command\CompleteVehicleMediaCleanupHandler;
use App\Modules\Vehicles\Application\Data\CleanupPolicy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class CleanupVehicleMedia implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = CleanupPolicy::MAX_ATTEMPTS;

    /** @var list<int> */
    public array $backoff = CleanupPolicy::RETRY_BACKOFF_SECONDS;

    public function __construct(public readonly int $taskId) {}

    public function handle(?CompleteVehicleMediaCleanupHandler $handler = null): void
    {
        ($handler ?? app(CompleteVehicleMediaCleanupHandler::class))->handle($this->taskId);
    }
}
