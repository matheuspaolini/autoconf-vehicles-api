<?php

namespace App\Http\Resources;

use App\Domain\Vehicles\Read\VehicleListRepresentation;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Vehicle */
class VehicleListResource extends JsonResource
{
    /** @param VehicleListRepresentation $resource */
    public function toArray(Request $request): array
    {
        $vehicle = $this->resource;

        return [
            'id' => $vehicle->id,
            'placa' => $vehicle->placa,
            'chassi' => $vehicle->chassi,
            'marca' => $vehicle->marca,
            'modelo' => $vehicle->modelo,
            'versao' => $vehicle->versao,
            'valor_venda' => $vehicle->valorVenda,
            'cor' => $vehicle->cor,
            'km' => $vehicle->km,
            'cambio' => match ($vehicle->cambio) {
                Transmission::Manual => 'manual',
                Transmission::Automatic => 'automatico',
            },
            'combustivel' => match ($vehicle->combustivel) {
                FuelType::Gasoline => 'gasolina',
                FuelType::Ethanol => 'alcool',
                FuelType::Flex => 'flex',
                FuelType::Diesel => 'diesel',
                FuelType::Hybrid => 'hibrido',
                FuelType::Electric => 'eletrico',
            },
            'owner' => ['id' => $vehicle->ownerId, 'name' => $vehicle->ownerName],
            'cover_image' => $vehicle->coverImage?->toArray(),
            'permissions' => [
                'update' => $vehicle->canUpdate,
                'delete' => $vehicle->canDelete,
                'manage_images' => $vehicle->canManageImages,
            ],
            'created_at' => $vehicle->createdAt,
            'updated_at' => $vehicle->updatedAt,
        ];
    }
}
