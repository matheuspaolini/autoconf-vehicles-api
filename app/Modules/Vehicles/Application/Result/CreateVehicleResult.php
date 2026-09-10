<?php

namespace App\Modules\Vehicles\Application\Result;

use App\Modules\Vehicles\Application\Data\VehicleData;

final readonly class CreateVehicleResult
{
    /** @param array<string, list<string>> $fieldErrors */
    private function __construct(
        public ?VehicleData $vehicle,
        public ?CreateVehicleError $error,
        public array $fieldErrors,
    ) {}

    public static function success(VehicleData $vehicle): self
    {
        return new self($vehicle, null, []);
    }

    /** @param array<string, list<string>> $fieldErrors */
    public static function failure(CreateVehicleError $error, array $fieldErrors = []): self
    {
        return new self(null, $error, $fieldErrors);
    }

    public function succeeded(): bool
    {
        return $this->error === null;
    }
}
