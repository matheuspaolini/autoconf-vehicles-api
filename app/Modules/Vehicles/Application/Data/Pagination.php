<?php

namespace App\Modules\Vehicles\Application\Data;

final readonly class Pagination
{
    public function __construct(
        public int $perPage,
        public ?int $page = null,
    ) {}
}
