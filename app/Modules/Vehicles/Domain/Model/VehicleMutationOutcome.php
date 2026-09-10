<?php

namespace App\Modules\Vehicles\Domain\Model;

final readonly class VehicleMutationOutcome
{
    private function __construct(public ?VehicleError $error) {}

    public static function success(): self
    {
        return new self(null);
    }

    public static function failure(VehicleError $error): self
    {
        return new self($error);
    }

    public function failed(): bool
    {
        return $this->error instanceof VehicleError;
    }
}
