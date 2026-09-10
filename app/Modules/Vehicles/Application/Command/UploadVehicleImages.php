<?php

namespace App\Modules\Vehicles\Application\Command;

use App\Modules\Vehicles\Application\Port\UploadSource;
use App\Modules\Vehicles\Domain\Model\Actor;

final readonly class UploadVehicleImages
{
    /** @param list<UploadSource> $files */
    public function __construct(
        public int $vehicleId,
        public ?int $expectedVersion,
        public bool $versionHeaderMissing,
        public string $idempotencyKey,
        public Actor $actor,
        public array $files,
    ) {}
}
