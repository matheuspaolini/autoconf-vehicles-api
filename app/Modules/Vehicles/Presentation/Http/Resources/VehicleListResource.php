<?php

namespace App\Modules\Vehicles\Presentation\Http\Resources;

use App\Modules\Vehicles\Application\Data\VehicleData;
use App\Modules\Vehicles\Domain\Enum\FuelType;
use App\Modules\Vehicles\Domain\Enum\Transmission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read VehicleData $resource */
final class VehicleListResource extends JsonResource
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
            'cambio' => match (Transmission::from($vehicle->details->transmission)) {
                Transmission::Manual => 'manual',
                Transmission::Automatic => 'automatico',
            },
            'combustivel' => match (FuelType::from($vehicle->details->fuelType)) {
                FuelType::Gasoline => 'gasolina',
                FuelType::Ethanol => 'alcool',
                FuelType::Flex => 'flex',
                FuelType::Diesel => 'diesel',
                FuelType::Hybrid => 'hibrido',
                FuelType::Electric => 'eletrico',
            },
            'owner' => ['id' => $vehicle->owner->id, 'name' => $vehicle->owner->name],
            'cover_image' => $vehicle->coverImage === null ? null : VehicleImageResource::make($vehicle->coverImage)->toArray($request),
            'permissions' => [
                'update' => $canMutate,
                'delete' => $canMutate,
                'manage_images' => $canMutate,
            ],
            'created_at' => $vehicle->createdAt,
            'updated_at' => $vehicle->updatedAt,
        ];
    }
}
