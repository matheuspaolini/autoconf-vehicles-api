<?php

namespace App\Modules\Vehicles\Application\Port;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
