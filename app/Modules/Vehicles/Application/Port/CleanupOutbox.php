<?php

namespace App\Modules\Vehicles\Application\Port;

use App\Modules\Vehicles\Application\Data\CleanupRequest;

interface CleanupOutbox
{
    public function record(CleanupRequest $request): int;
}
