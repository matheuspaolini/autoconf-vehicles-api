<?php

namespace App\Modules\Vehicles\Domain\ValueObject;

use App\Modules\Vehicles\Domain\Exception\InvalidVehicleValue;

final readonly class VehicleVersion
{
    public function __construct(public int $value)
    {
        if ($value < 1) {
            throw InvalidVehicleValue::forField('version');
        }
    }

    public function next(): self
    {
        return new self($this->value + 1);
    }
}
