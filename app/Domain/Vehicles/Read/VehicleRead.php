<?php

namespace App\Domain\Vehicles\Read;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class VehicleRead
{
    public function __construct(
        private readonly VehicleCatalogGrammar $catalogGrammar,
    ) {}

    /** @return LengthAwarePaginator<int, Vehicle> */
    public function catalog(VehicleCatalogCriteria $criteria, User $viewer): LengthAwarePaginator
    {
        $vehicles = $this->catalogGrammar->apply(
            Vehicle::query()->with(['owner', 'coverImage']),
            $criteria,
            $viewer,
        );

        return $vehicles
            ->paginate(perPage: $criteria->perPage, page: $criteria->page)
            ->withQueryString()
            ->through(static fn (Vehicle $vehicle): Vehicle => $vehicle);
    }

    public function detail(int $vehicleId): Vehicle
    {
        /** @var Vehicle $vehicle */
        $vehicle = Vehicle::query()
            ->with(['owner', 'creator', 'updater', 'coverImage'])
            ->findOrFail($vehicleId);

        return $vehicle;
    }

    /** @return LengthAwarePaginator<int, VehicleImage> */
    public function gallery(Vehicle $vehicle, int $perPage): LengthAwarePaginator
    {
        return $vehicle->images()
            ->orderByDesc('is_cover')
            ->orderBy('id')
            ->paginate($perPage)
            ->through(static fn (VehicleImage $image): VehicleImage => $image);
    }
}
