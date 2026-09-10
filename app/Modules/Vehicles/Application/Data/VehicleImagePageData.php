<?php

namespace App\Modules\Vehicles\Application\Data;

final readonly class VehicleImagePageData
{
    public function __construct(
        public PageData $page,
        public int $vehicleVersion,
    ) {}
}
