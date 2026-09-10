<?php

namespace App\Modules\Vehicles\Application\Result;

final readonly class DeleteVehicleResult
{
    private function __construct(public ?DeleteVehicleError $error) {}

    public static function success(): self
    {
        return new self(null);
    }

    public static function failure(DeleteVehicleError $error): self
    {
        return new self($error);
    }

    public function succeeded(): bool
    {
        return $this->error === null;
    }
}
