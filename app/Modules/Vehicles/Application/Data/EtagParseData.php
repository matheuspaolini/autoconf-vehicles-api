<?php

namespace App\Modules\Vehicles\Application\Data;

final readonly class EtagParseData
{
    public function __construct(
        public ?int $version,
        public bool $missing,
    ) {}
}
