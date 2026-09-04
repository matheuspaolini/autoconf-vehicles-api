<?php

namespace App\Domain\Vehicles\Read;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

final class VehicleRepresentationRead
{
    public function __construct(
        private readonly VehicleCatalogGrammar $catalogGrammar,
    ) {}

    /** @return LengthAwarePaginator<int, VehicleListRepresentation> */
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
            ->through(fn (Vehicle $vehicle): VehicleListRepresentation => $this->listRepresentation($vehicle, $viewer));
    }

    public function detail(int $vehicleId, User $viewer): VehicleDetailRepresentation
    {
        /** @var Vehicle $vehicle */
        $vehicle = Vehicle::query()
            ->with(['owner', 'creator', 'updater', 'coverImage'])
            ->findOrFail($vehicleId);

        return new VehicleDetailRepresentation(
            vehicle: $this->listRepresentation($vehicle, $viewer),
            audit: new VehicleAuditRepresentation(
                createdAt: $vehicle->created_at?->toISOString(),
                createdById: $vehicle->creator->getKey(),
                createdByName: $vehicle->creator->name,
                updatedAt: $vehicle->updated_at?->toISOString(),
                updatedById: $vehicle->updater->getKey(),
                updatedByName: $vehicle->updater->name,
            ),
        );
    }

    /** @return LengthAwarePaginator<int, VehicleImageRepresentation> */
    public function gallery(Vehicle $vehicle, int $perPage): LengthAwarePaginator
    {
        return $vehicle->images()
            ->orderByDesc('is_cover')
            ->orderBy('id')
            ->paginate($perPage)
            ->through(fn (VehicleImage $image): VehicleImageRepresentation => $this->image($image));
    }

    public function image(VehicleImage $image): VehicleImageRepresentation
    {
        return new VehicleImageRepresentation(
            id: $image->getKey(),
            url: url(Storage::disk('public')->url($image->path)),
            isCover: $image->is_cover,
            createdAt: $image->created_at?->toISOString(),
        );
    }

    private function listRepresentation(Vehicle $vehicle, User $viewer): VehicleListRepresentation
    {
        return new VehicleListRepresentation(
            id: $vehicle->getKey(),
            lockVersion: $vehicle->lock_version,
            placa: $vehicle->placa,
            chassi: $vehicle->chassi,
            marca: $vehicle->marca,
            modelo: $vehicle->modelo,
            versao: $vehicle->versao,
            valorVenda: \number_format((float) $vehicle->valor_venda, 2, '.', ''),
            cor: $vehicle->cor,
            km: $vehicle->km,
            cambio: $vehicle->cambio,
            combustivel: $vehicle->combustivel,
            ownerId: $vehicle->owner->getKey(),
            ownerName: $vehicle->owner->name,
            coverImage: $vehicle->coverImage instanceof VehicleImage ? $this->image($vehicle->coverImage) : null,
            canUpdate: $viewer->can('update', $vehicle),
            canDelete: $viewer->can('delete', $vehicle),
            canManageImages: $viewer->can('manageImages', $vehicle),
            createdAt: $vehicle->created_at?->toISOString(),
            updatedAt: $vehicle->updated_at?->toISOString(),
        );
    }
}
