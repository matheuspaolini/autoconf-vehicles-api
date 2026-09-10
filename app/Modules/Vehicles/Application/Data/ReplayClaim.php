<?php

namespace App\Modules\Vehicles\Application\Data;

final readonly class ReplayClaim
{
    /** @param array<string, mixed>|null $responseBody */
    public function __construct(
        public int $id,
        public string $status,
        public ?int $responseStatus = null,
        public ?array $responseBody = null,
        public ?string $responseEtag = null,
    ) {}
}
