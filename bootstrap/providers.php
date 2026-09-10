<?php

use App\Modules\Vehicles\Infrastructure\Providers\VehiclesServiceProvider;
use App\Modules\Vehicles\Presentation\VehiclesPresentationServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\OpenApiServiceProvider;

return [
    AppServiceProvider::class,
    VehiclesServiceProvider::class,
    VehiclesPresentationServiceProvider::class,
    OpenApiServiceProvider::class,
];
