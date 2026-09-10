<?php

namespace App\Modules\Vehicles\Domain\ValueObject;

use App\Modules\Vehicles\Domain\Exception\InvalidVehicleValue;

final readonly class VehicleImageId
{
    public function __construct(public int $value)
    {
        if ($value < 1) {
            throw InvalidVehicleValue::forField('image_id');
        }
    }
}
