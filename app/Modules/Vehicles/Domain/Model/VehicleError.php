<?php

namespace App\Modules\Vehicles\Domain\Model;

enum VehicleError: string
{
    case Forbidden = 'forbidden';
    case VersionConflict = 'version_conflict';
    case GalleryCapacityExceeded = 'gallery_capacity_exceeded';
    case ImageNotFound = 'image_not_found';
}
