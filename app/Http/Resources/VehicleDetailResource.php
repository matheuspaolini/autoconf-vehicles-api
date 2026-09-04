<?php

namespace App\Http\Resources;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read Vehicle $resource */
class VehicleDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $vehicle = $this->resource;

        return [
            ...(new VehicleListResource($vehicle))->toArray($request),
            'audit' => [
                'created_at' => $vehicle->created_at?->toISOString(),
                'created_by' => ['id' => $vehicle->creator->getKey(), 'name' => $vehicle->creator->name],
                'updated_at' => $vehicle->updated_at?->toISOString(),
                'updated_by' => ['id' => $vehicle->updater->getKey(), 'name' => $vehicle->updater->name],
            ],
        ];
    }
}
