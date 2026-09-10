<?php

namespace App\Modules\Vehicles\Domain\Model;

use App\Modules\Vehicles\Domain\ValueObject\VehicleImageId;
use DateTimeImmutable;

final class VehicleImage
{
    public function __construct(
        public readonly ?VehicleImageId $id,
        public readonly string $path,
        public bool $isCover,
        public readonly DateTimeImmutable $createdAt,
    ) {}
}
