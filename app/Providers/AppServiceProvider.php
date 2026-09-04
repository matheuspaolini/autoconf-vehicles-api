<?php

namespace App\Providers;

use App\Domain\Vehicles\VehicleGallery\VehicleImageLifecycle;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app
            ->when(VehicleImageLifecycle::class)
            ->needs(Filesystem::class)
            ->give(fn (): Filesystem => Storage::disk('public'));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::wrap('data');

        RateLimiter::for('authentication', function (Request $request): Limit {
            $email = Str::lower((string) $request->input('email'));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('uploads', function (Request $request): Limit {
            return Limit::perMinute(20)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });

        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(60)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });
    }
}
