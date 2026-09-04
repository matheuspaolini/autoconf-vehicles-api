<?php

namespace App\Http\Resources;

use App\Domain\Vehicles\Read\VehicleImageRepresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read VehicleImageRepresentation $resource */
class VehicleImageResource extends JsonResource
{
    /** @param VehicleImageRepresentation $resource */
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
