<?php

namespace App\Modules\Vehicles\Infrastructure\Persistence;

use App\Modules\Vehicles\Application\Port\TransactionRunner;
use Closure;
use Illuminate\Support\Facades\DB;

final class LaravelTransactionRunner implements TransactionRunner
{
    public function run(Closure $operation): mixed
    {
        return DB::transaction($operation);
    }
}
