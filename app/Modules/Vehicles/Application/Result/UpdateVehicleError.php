<?php

namespace App\Modules\Vehicles\Application\Result;

enum UpdateVehicleError: string
{
    case NotFound = 'not_found';
    case Forbidden = 'forbidden';
    case InvalidInput = 'invalid_input';
    case VersionConflict = 'version_conflict';
    case DuplicatePlate = 'duplicate_plate';
    case DuplicateChassis = 'duplicate_chassis';
}
