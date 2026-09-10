<?php

namespace App\Modules\Vehicles\Domain\ValueObject;

use App\Modules\Vehicles\Domain\Exception\InvalidVehicleValue;

final readonly class Mileage
{
    public function __construct(public int $kilometres)
    {
        if ($kilometres < 0) {
            throw InvalidVehicleValue::forField('km');
        }
    }
}
