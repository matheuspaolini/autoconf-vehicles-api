<?php

namespace App\Domain\Vehicles\Read;

final readonly class VehicleCatalogCriteria
{
    /**
     * @param  array<string, string|null>  $filters
     * @param  list<string>  $sortTerms
     */
    public function __construct(
        public array $filters,
        public ?string $search,
        public bool $isMine,
        public array $sortTerms,
        public int $perPage,
        public ?int $page,
    ) {}

    public function filter(string $name): ?string
    {
        return $this->filters[$name] ?? null;
    }
}
