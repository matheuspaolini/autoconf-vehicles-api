<?php

namespace App\Modules\Vehicles\Application\Data;

final readonly class VehicleCatalogCriteria
{
    public const DEFAULT_PER_PAGE = 15;

    public const MAXIMUM_PER_PAGE = 100;

    /**
     * @param  array<string, string|null>  $filters
     * @param  list<string>  $sortTerms
     */
    public function __construct(
        public array $filters,
        public ?string $search,
        public ?int $ownerId,
        public array $sortTerms,
        public int $perPage = self::DEFAULT_PER_PAGE,
        public ?int $page = null,
    ) {}
}
