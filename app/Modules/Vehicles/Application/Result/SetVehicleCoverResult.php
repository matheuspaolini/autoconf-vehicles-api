<?php

namespace App\Modules\Vehicles\Application\Result;

use App\Modules\Vehicles\Application\Data\VehicleImageData;

final readonly class SetVehicleCoverResult
{
    private function __construct(
        public ?VehicleImageData $image,
        public ?int $vehicleVersion,
        public ?SetVehicleCoverError $error,
    ) {}

    public static function success(VehicleImageData $image, int $vehicleVersion): self
    {
        return new self($image, $vehicleVersion, null);
    }

    public static function failure(SetVehicleCoverError $error): self
    {
        return new self(null, null, $error);
    }

    public function succeeded(): bool
    {
        return $this->error === null;
    }
}
