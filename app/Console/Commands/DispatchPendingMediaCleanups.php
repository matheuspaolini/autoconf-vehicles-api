<?php

namespace App\Console\Commands;

use App\Domain\Vehicles\VehicleMediaRetirement;
use App\Models\VehicleUploadRequest;
use Illuminate\Console\Command;

class DispatchPendingMediaCleanups extends Command
{
    protected $signature = 'media:reconcile-cleanup';

    protected $description = 'Dispatch unfinished Vehicle media cleanup tasks.';

    public function handle(VehicleMediaRetirement $mediaRetirement): int
    {
        $mediaRetirement->reconcile();
        $this->pruneExpiredVehicleUploadRequests();

        return self::SUCCESS;
    }

    private function pruneExpiredVehicleUploadRequests(): void
    {
        VehicleUploadRequest::query()->where('expires_at', '<=', now())->delete();
    }
}
