<?php

namespace App\Modules\Vehicles\Application\Data;

final readonly class PageData
{
    /** @param list<object> $items */
    public function __construct(
        public array $items,
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
        public ?string $firstPageUrl,
        public ?string $lastPageUrl,
        public ?string $nextPageUrl,
        public ?string $previousPageUrl,
        public ?int $from,
        public ?int $to,
        public string $path,
        /** @var list<array{url: string|null, label: string, active: bool}> */
        public array $links,
    ) {}
}
