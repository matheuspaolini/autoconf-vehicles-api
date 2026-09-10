<?php

namespace App\Modules\Vehicles\Application\Command;

use App\Modules\Vehicles\Application\Data\CleanupPolicy;
use App\Modules\Vehicles\Application\Port\CleanupStore;
use App\Modules\Vehicles\Application\Port\Clock;

final readonly class ReconcileVehicleMediaHandler
{
    public function __construct(
        private CleanupStore $cleanups,
        private Clock $clock,
    ) {}

    public function handle(): void
    {
        $now = $this->clock->now();
        $redispatchCutoff = $now->modify('-'.CleanupPolicy::RECONCILIATION_INTERVAL_MINUTES.' minutes');

        foreach ($this->cleanups->overdueTaskIds($redispatchCutoff, CleanupPolicy::RECONCILIATION_BATCH_SIZE) as $taskId) {
            $this->cleanups->dispatch($taskId);
        }

        $this->cleanups->removeOrphans($now->modify('-'.CleanupPolicy::ORPHAN_GRACE_MINUTES.' minutes'));
        $this->cleanups->pruneExpiredUploadRequests($now);
    }
}
