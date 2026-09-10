<?php

namespace App\Modules\Vehicles\Application\Data;

final readonly class RenderedUploadResponse
{
    /** @param array<string, mixed> $body */
    public function __construct(
        public array $body,
        public int $status,
        public string $etag,
    ) {}
}
