<?php

namespace App\Modules\Vehicles\Presentation\Console;

use App\Modules\Vehicles\Application\Command\ReconcileVehicleMediaHandler;
use Illuminate\Console\Command;

class DispatchPendingMediaCleanups extends Command
{
    protected $signature = 'media:reconcile-cleanup';

    protected $description = 'Dispatch unfinished Vehicle media cleanup tasks.';

    public function handle(ReconcileVehicleMediaHandler $handler): int
    {
        $handler->handle();

        return self::SUCCESS;
    }
}
