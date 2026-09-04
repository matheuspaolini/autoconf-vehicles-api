<?php

namespace App\Http\Resources;

use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read Vehicle $resource */
class VehicleListResource extends JsonResource
{
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
            'valor_venda' => \number_format((float) $vehicle->valor_venda, 2, '.', ''),
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
            'owner' => ['id' => $vehicle->owner->getKey(), 'name' => $vehicle->owner->name],
            'cover_image' => $vehicle->coverImage instanceof VehicleImage ? VehicleImageResource::make($vehicle->coverImage)->toArray($request) : null,
            'permissions' => [
                'update' => $request->user()?->can('update', $vehicle) ?? false,
                'delete' => $request->user()?->can('delete', $vehicle) ?? false,
                'manage_images' => $request->user()?->can('manageImages', $vehicle) ?? false,
            ],
            'created_at' => $vehicle->created_at?->toISOString(),
            'updated_at' => $vehicle->updated_at?->toISOString(),
        ];
    }
}
