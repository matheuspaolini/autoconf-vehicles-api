<?php

namespace App\Modules\Vehicles\Application\Data;

final readonly class VehicleDetailsData
{
    public function __construct(
        public string $plate,
        public string $chassis,
        public string $brand,
        public string $model,
        public string $trim,
        public string $salePrice,
        public string $color,
        public int $mileage,
        public string $transmission,
        public string $fuelType,
    ) {}
}
