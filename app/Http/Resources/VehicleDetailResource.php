<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class VehicleDetailResource extends VehicleListResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'images' => VehicleImageResource::collection($this->whenLoaded('images')),
            'audit' => [
                'created_at' => $this->created_at?->toISOString(),
                'created_by' => ['id' => $this->creator->id, 'name' => $this->creator->name],
                'updated_at' => $this->updated_at?->toISOString(),
                'updated_by' => ['id' => $this->updater->id, 'name' => $this->updater->name],
            ],
        ];
    }
}
