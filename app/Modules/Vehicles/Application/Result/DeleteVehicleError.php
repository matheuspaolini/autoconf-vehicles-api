<?php

namespace App\Modules\Vehicles\Application\Result;

enum DeleteVehicleError: string
{
    case NotFound = 'not_found';
    case Forbidden = 'forbidden';
    case VersionConflict = 'version_conflict';
}
