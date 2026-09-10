<?php

namespace App\Modules\Vehicles\Application\Port;

use Closure;

interface TransactionRunner
{
    /** @template T @param Closure(): T $operation @return T */
    public function run(Closure $operation): mixed;
}
