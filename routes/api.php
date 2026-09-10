<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\Auth\RegisterController;
use App\Modules\Vehicles\Presentation\Http\Controllers\DeleteVehicleImageController;
use App\Modules\Vehicles\Presentation\Http\Controllers\SetVehicleCoverController;
use App\Modules\Vehicles\Presentation\Http\Controllers\UploadVehicleImagesController;
use App\Modules\Vehicles\Presentation\Http\Controllers\VehicleController;
use App\Modules\Vehicles\Presentation\Http\Controllers\VehicleImageIndexController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', RegisterController::class)->middleware('throttle:authentication');
    Route::post('/login', LoginController::class)->middleware('throttle:authentication');
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', LogoutController::class);
        Route::get('/me', MeController::class);
    });
});
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::apiResource('vehicles', VehicleController::class)->where(['vehicle' => '[1-9][0-9]*']);
    Route::get('/vehicles/{vehicle}/images', VehicleImageIndexController::class)->where('vehicle', '[1-9][0-9]*');
    Route::post('/vehicles/{vehicle}/images', UploadVehicleImagesController::class)
        ->where('vehicle', '[1-9][0-9]*')
        ->middleware('throttle:uploads');
    Route::scopeBindings()->group(function (): void {
        Route::patch('/vehicles/{vehicle}/images/{image}/cover', SetVehicleCoverController::class)
            ->where(['vehicle' => '[1-9][0-9]*', 'image' => '[1-9][0-9]*']);
        Route::delete('/vehicles/{vehicle}/images/{image}', DeleteVehicleImageController::class)
            ->where(['vehicle' => '[1-9][0-9]*', 'image' => '[1-9][0-9]*']);
    });
});
