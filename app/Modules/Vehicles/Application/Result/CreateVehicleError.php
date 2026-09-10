<?php

namespace App\Modules\Vehicles\Application\Result;

enum CreateVehicleError: string
{
    case InvalidInput = 'invalid_input';
    case DuplicatePlate = 'duplicate_plate';
    case DuplicateChassis = 'duplicate_chassis';
}
