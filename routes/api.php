<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DeleteVehicleImageController;
use App\Http\Controllers\SetVehicleCoverController;
use App\Http\Controllers\UploadVehicleImagesController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleImageIndexController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->middleware('web')->group(function (): void {
    Route::post('/register', RegisterController::class)->middleware('throttle:authentication');
    Route::post('/login', LoginController::class)->middleware('throttle:authentication');
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', LogoutController::class);
        Route::get('/me', MeController::class);
    });
});
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::apiResource('vehicles', VehicleController::class);
    Route::get('/vehicles/{vehicle}/images', VehicleImageIndexController::class);
    Route::post('/vehicles/{vehicle}/images', UploadVehicleImagesController::class)->middleware('throttle:uploads');
    Route::scopeBindings()->group(function (): void {
        Route::patch('/vehicles/{vehicle}/images/{image}/cover', SetVehicleCoverController::class);
        Route::delete('/vehicles/{vehicle}/images/{image}', DeleteVehicleImageController::class);
    });
});
