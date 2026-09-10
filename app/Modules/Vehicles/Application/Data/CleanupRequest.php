<?php

namespace App\Modules\Vehicles\Application\Data;

final readonly class CleanupRequest
{
    /** @param list<string> $paths */
    public function __construct(
        public array $paths,
        public ?string $directory,
    ) {}
}
