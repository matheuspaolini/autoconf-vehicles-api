<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VehicleImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'url' => url(Storage::disk('public')->url($this->path)), 'is_cover' => $this->is_cover, 'created_at' => $this->created_at?->toISOString()];
    }
}
