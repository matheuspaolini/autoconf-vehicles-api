<?php

namespace App\Modules\Vehicles\Domain\Exception;

use InvalidArgumentException;

final class InvalidVehicleValue extends InvalidArgumentException
{
    public static function forField(string $field): self
    {
        return new self("The {$field} value is invalid.");
    }
}
