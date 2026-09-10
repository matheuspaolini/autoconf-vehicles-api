<?php

namespace App\Modules\Vehicles\Application\Exception;

use RuntimeException;

final class DuplicateVehicleValue extends RuntimeException
{
    public function __construct(public readonly string $field)
    {
        parent::__construct("A Vehicle with this {$field} already exists.");
    }
}
