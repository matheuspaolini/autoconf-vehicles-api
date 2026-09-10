<?php

namespace App\Modules\Vehicles\Application\Result;

use App\Modules\Vehicles\Application\Data\RenderedUploadResponse;

final readonly class UploadVehicleImagesResult
{
    private function __construct(
        public ?RenderedUploadResponse $response,
        public ?UploadVehicleImagesError $error,
        public bool $retryLater = false,
    ) {}

    public static function success(RenderedUploadResponse $response): self
    {
        return new self($response, null);
    }

    public static function failure(UploadVehicleImagesError $error, bool $retryLater = false): self
    {
        return new self(null, $error, $retryLater);
    }

    public function succeeded(): bool
    {
        return $this->error === null;
    }
}
