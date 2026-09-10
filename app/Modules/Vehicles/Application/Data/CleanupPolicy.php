<?php

namespace App\Modules\Vehicles\Application\Data;

final class CleanupPolicy
{
    public const RECONCILIATION_INTERVAL_MINUTES = 15;

    public const RECONCILIATION_BATCH_SIZE = 100;

    public const ORPHAN_GRACE_MINUTES = 60;

    public const MAX_ATTEMPTS = 3;

    public const RETRY_BACKOFF_SECONDS = [5, 60, 300];
}
