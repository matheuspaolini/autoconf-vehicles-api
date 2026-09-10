<?php

namespace App\Modules\Vehicles\Domain\Model;

use App\Modules\Vehicles\Domain\Enum\FuelType;
use App\Modules\Vehicles\Domain\Enum\Transmission;
use App\Modules\Vehicles\Domain\Exception\InvalidVehicleValue;
use App\Modules\Vehicles\Domain\ValueObject\Chassis;
use App\Modules\Vehicles\Domain\ValueObject\Mileage;
use App\Modules\Vehicles\Domain\ValueObject\Money;
use App\Modules\Vehicles\Domain\ValueObject\Plate;

final readonly class VehicleDetails
{
    public function __construct(
        public Plate $plate,
        public Chassis $chassis,
        public string $brand,
        public string $model,
        public string $trim,
        public Money $salePrice,
        public string $color,
        public Mileage $mileage,
        public Transmission $transmission,
        public FuelType $fuelType,
    ) {
        foreach (['marca' => $brand, 'modelo' => $model, 'versao' => $trim, 'cor' => $color] as $field => $value) {
            if ($value === '' || \mb_strlen($value) > 100) {
                throw InvalidVehicleValue::forField($field);
            }
        }
    }
}
