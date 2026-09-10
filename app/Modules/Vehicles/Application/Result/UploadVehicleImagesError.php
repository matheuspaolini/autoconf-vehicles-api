<?php

namespace App\Modules\Vehicles\Application\Result;

enum UploadVehicleImagesError: string
{
    case NotFound = 'not_found';
    case Forbidden = 'forbidden';
    case VersionConflict = 'version_conflict';
    case MissingVersion = 'missing_version';
    case GalleryCapacityExceeded = 'gallery_capacity_exceeded';
    case IdempotencyConflict = 'idempotency_conflict';
    case UploadProcessing = 'upload_processing';
}
