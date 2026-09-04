<?php

namespace App\Http\Resources;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Vehicle */
class VehicleListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canUpdate = (bool) $request->user()?->can('update', $this->resource);

        return [
            'id' => $this->id,
            'placa' => $this->placa,
            'chassi' => $this->chassi,
            'marca' => $this->marca,
            'modelo' => $this->modelo,
            'versao' => $this->versao,
            'valor_venda' => number_format((float) $this->valor_venda, 2, '.', ''),
            'cor' => $this->cor,
            'km' => $this->km,
            /** @var 'manual'|'automatico' */
            'cambio' => $this->cambio->value,
            /** @var 'gasolina'|'alcool'|'flex'|'diesel'|'hibrido'|'eletrico' */
            'combustivel' => $this->combustivel->value,
            'owner' => ['id' => $this->owner->id, 'name' => $this->owner->name],
            'cover_image' => $this->coverImage ? VehicleImageResource::make($this->coverImage) : null,
            'permissions' => [
                'update' => $canUpdate,
                'delete' => (bool) $request->user()?->can('delete', $this->resource),
                'manage_images' => (bool) $request->user()?->can('manageImages', $this->resource),
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
