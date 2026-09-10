<?php

namespace App\Modules\Vehicles\Infrastructure;

use App\Modules\Vehicles\Application\Port\Clock;
use DateTimeImmutable;
use Illuminate\Support\Carbon;

final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return Carbon::now()->toDateTimeImmutable();
    }
}
