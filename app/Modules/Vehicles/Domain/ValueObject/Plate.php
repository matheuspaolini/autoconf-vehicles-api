<?php

namespace App\Modules\Vehicles\Domain\ValueObject;

use App\Modules\Vehicles\Domain\Exception\InvalidVehicleValue;

final readonly class Plate
{
    private const PATTERN = '/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/D';

    public string $value;

    public function __construct(string $value)
    {
        $normalized = \strtoupper(\trim($value));

        if (\preg_match(self::PATTERN, $normalized) !== 1) {
            throw InvalidVehicleValue::forField('placa');
        }

        $this->value = $normalized;
    }
}
