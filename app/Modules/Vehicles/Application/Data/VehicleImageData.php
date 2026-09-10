<?php

namespace App\Modules\Vehicles\Application\Data;

final readonly class VehicleImageData
{
    public function __construct(
        public int $id,
        public string $path,
        public bool $isCover,
        public string $createdAt,
    ) {}
}
