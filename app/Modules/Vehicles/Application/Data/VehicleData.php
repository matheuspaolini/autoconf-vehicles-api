<?php

namespace App\Modules\Vehicles\Application\Data;

final readonly class VehicleData
{
    /** @param list<VehicleImageData> $images */
    public function __construct(
        public int $id,
        public VehicleDetailsData $details,
        public int $version,
        public UserSummaryData $owner,
        public ?VehicleImageData $coverImage,
        public array $images,
        public UserSummaryData $creator,
        public UserSummaryData $updater,
        public string $createdAt,
        public string $updatedAt,
    ) {}
}
