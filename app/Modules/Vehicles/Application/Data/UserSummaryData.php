<?php

namespace App\Modules\Vehicles\Application\Data;

final readonly class UserSummaryData
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}
