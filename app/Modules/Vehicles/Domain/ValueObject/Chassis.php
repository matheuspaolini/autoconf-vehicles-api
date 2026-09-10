<?php

namespace App\Modules\Vehicles\Domain\ValueObject;

use App\Modules\Vehicles\Domain\Exception\InvalidVehicleValue;

final readonly class Chassis
{
    private const PATTERN = '/^[A-HJ-NPR-Z0-9]{17}$/D';

    public string $value;

    public function __construct(string $value)
    {
        $normalized = \strtoupper(\trim($value));

        if (\preg_match(self::PATTERN, $normalized) !== 1) {
            throw InvalidVehicleValue::forField('chassi');
        }

        $this->value = $normalized;
    }
}
