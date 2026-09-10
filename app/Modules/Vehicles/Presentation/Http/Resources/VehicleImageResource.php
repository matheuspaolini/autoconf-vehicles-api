<?php

namespace App\Modules\Vehicles\Presentation\Http\Resources;

use App\Modules\Vehicles\Application\Data\VehicleImageData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @property-read VehicleImageData $resource */
final class VehicleImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'url' => url(Storage::disk('public')->url($this->resource->path)),
            'is_cover' => $this->resource->isCover,
            'created_at' => $this->resource->createdAt,
        ];
    }
}
