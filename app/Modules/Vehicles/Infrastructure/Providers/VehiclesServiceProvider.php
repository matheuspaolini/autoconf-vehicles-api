<?php

namespace App\Modules\Vehicles\Infrastructure\Providers;

use App\Modules\Vehicles\Application\Port\CleanupOutbox;
use App\Modules\Vehicles\Application\Port\CleanupStore;
use App\Modules\Vehicles\Application\Port\Clock;
use App\Modules\Vehicles\Application\Port\MediaStore;
use App\Modules\Vehicles\Application\Port\TransactionRunner;
use App\Modules\Vehicles\Application\Port\UploadReplayStore;
use App\Modules\Vehicles\Application\Port\VehicleReadGateway;
use App\Modules\Vehicles\Application\Port\VehicleWriteGateway;
use App\Modules\Vehicles\Infrastructure\Media\LaravelMediaStore;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\EloquentCleanupOutbox;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\EloquentCleanupStore;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\EloquentUploadReplayStore;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\EloquentVehicleReadGateway;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\EloquentVehicleWriteGateway;
use App\Modules\Vehicles\Infrastructure\Persistence\LaravelTransactionRunner;
use App\Modules\Vehicles\Infrastructure\SystemClock;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

final class VehiclesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Clock::class, SystemClock::class);
        $this->app->bind(CleanupOutbox::class, EloquentCleanupOutbox::class);
        $this->app->bind(CleanupStore::class, EloquentCleanupStore::class);
        $this->app->bind(MediaStore::class, LaravelMediaStore::class);
        $this->app->bind(TransactionRunner::class, LaravelTransactionRunner::class);
        $this->app->bind(UploadReplayStore::class, EloquentUploadReplayStore::class);
        $this->app->bind(VehicleReadGateway::class, EloquentVehicleReadGateway::class);
        $this->app->bind(VehicleWriteGateway::class, EloquentVehicleWriteGateway::class);

        $this->app
            ->when(LaravelMediaStore::class)
            ->needs(Filesystem::class)
            ->give(fn (): Filesystem => Storage::disk('public'));
    }
}
