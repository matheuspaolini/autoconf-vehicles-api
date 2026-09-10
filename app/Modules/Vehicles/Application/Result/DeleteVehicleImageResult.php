<?php

namespace App\Modules\Vehicles\Application\Result;

final readonly class DeleteVehicleImageResult
{
    private function __construct(public ?DeleteVehicleImageError $error) {}

    public static function success(): self
    {
        return new self(null);
    }

    public static function failure(DeleteVehicleImageError $error): self
    {
        return new self($error);
    }

    public function succeeded(): bool
    {
        return $this->error === null;
    }
}
