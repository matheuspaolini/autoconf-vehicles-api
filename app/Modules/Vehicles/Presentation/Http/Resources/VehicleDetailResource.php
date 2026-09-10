<?php

namespace App\Modules\Vehicles\Presentation\Http\Resources;

use App\Modules\Vehicles\Application\Data\VehicleData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read VehicleData $resource */
final class VehicleDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $vehicle = $this->resource;
        $actor = $request->user();
        $canMutate = ($actor?->is_admin ?? false) || $vehicle->owner->id === $actor?->getKey();

        return [
            'id' => $vehicle->id,
            'placa' => $vehicle->details->plate,
            'chassi' => $vehicle->details->chassis,
            'marca' => $vehicle->details->brand,
            'modelo' => $vehicle->details->model,
            'versao' => $vehicle->details->trim,
            'valor_venda' => $vehicle->details->salePrice,
            'cor' => $vehicle->details->color,
            'km' => $vehicle->details->mileage,
            'cambio' => $vehicle->details->transmission,
            'combustivel' => $vehicle->details->fuelType,
            'owner' => ['id' => $vehicle->owner->id, 'name' => $vehicle->owner->name],
            'cover_image' => $vehicle->coverImage === null ? null : VehicleImageResource::make($vehicle->coverImage)->toArray($request),
            'permissions' => [
                'update' => $canMutate,
                'delete' => $canMutate,
                'manage_images' => $canMutate,
            ],
            'created_at' => $vehicle->createdAt,
            'updated_at' => $vehicle->updatedAt,
            'images' => VehicleImageResource::collection($vehicle->images),
            'audit' => [
                'created_at' => $vehicle->createdAt,
                'created_by' => ['id' => $vehicle->creator->id, 'name' => $vehicle->creator->name],
                'updated_at' => $vehicle->updatedAt,
                'updated_by' => ['id' => $vehicle->updater->id, 'name' => $vehicle->updater->name],
            ],
        ];
    }
}
