<?php

namespace App\Modules\Vehicles\Presentation;

use App\Modules\Vehicles\Application\Port\UploadedImagesResponse;
use App\Modules\Vehicles\Presentation\Http\Resources\LaravelUploadedImagesResponse;
use Illuminate\Support\ServiceProvider;

final class VehiclesPresentationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UploadedImagesResponse::class, LaravelUploadedImagesResponse::class);
    }
}
