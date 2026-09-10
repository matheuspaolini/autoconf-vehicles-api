<?php

namespace App\Modules\Vehicles\Application\Result;

enum DeleteVehicleImageError: string
{
    case NotFound = 'not_found';
    case Forbidden = 'forbidden';
    case VersionConflict = 'version_conflict';
    case ImageNotFound = 'image_not_found';
}
