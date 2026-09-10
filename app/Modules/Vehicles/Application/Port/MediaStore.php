<?php

namespace App\Modules\Vehicles\Application\Port;

use App\Modules\Vehicles\Application\Data\StoredImage;
use App\Modules\Vehicles\Domain\ValueObject\VehicleId;

interface MediaStore
{
    public function store(VehicleId $vehicleId, UploadSource $source): StoredImage;

    /** @param list<string> $paths */
    public function delete(array $paths): void;

    public function deleteDirectory(string $directory): void;
}
