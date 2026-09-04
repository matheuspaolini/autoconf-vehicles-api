<?php

namespace App\Queries;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class VehicleRead
{
    /** @return Builder<Vehicle> */
    public function forCatalog(): Builder
    {
        return Vehicle::query()->with([
            'owner',
            'coverImage',
        ]);
    }

    public function detail(int $vehicleId): Vehicle
    {
        return Vehicle::query()
            ->with([
                'owner',
                'creator',
                'updater',
                'coverImage',
            ])
            ->findOrFail($vehicleId);
    }

    /** @return LengthAwarePaginator<int, VehicleImage> */
    public function gallery(Vehicle $vehicle, int $perPage): LengthAwarePaginator
    {
        return $vehicle->images()
            ->orderByDesc('is_cover')
            ->orderBy('id')
            ->paginate($perPage);
    }
}
