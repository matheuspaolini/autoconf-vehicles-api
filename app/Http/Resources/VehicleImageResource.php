<?php

namespace App\Http\Resources;

use App\Models\VehicleImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @property-read VehicleImage $resource */
class VehicleImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $image = $this->resource;

        return [
            'id' => $image->getKey(),
            'url' => url(Storage::disk('public')->url($image->path)),
            'is_cover' => $image->is_cover,
            'created_at' => $image->created_at?->toISOString(),
        ];
    }
}
