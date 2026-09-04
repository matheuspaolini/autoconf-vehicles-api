<?php

namespace App\Http\Resources;

use App\Models\Vehicle;
use Illuminate\Http\Request;

/** @mixin Vehicle */
class VehicleDetailResource extends VehicleListResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'images' => VehicleImageResource::collection($this->whenLoaded('images')),
            'audit' => [
                'created_at' => $this->created_at?->toISOString(),
                'created_by' => [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ],
                'updated_at' => $this->updated_at?->toISOString(),
                'updated_by' => [
                    'id' => $this->updater->id,
                    'name' => $this->updater->name,
                ],
            ],
        ]);
    }
}
