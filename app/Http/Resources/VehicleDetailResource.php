<?php

namespace App\Http\Resources;

use App\Domain\Vehicles\Read\VehicleDetailRepresentation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property-read VehicleDetailRepresentation $resource */
class VehicleDetailResource extends JsonResource
{
    /** @param VehicleDetailRepresentation $resource */
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
