<?php

namespace App\Domain\Vehicles\Read;

final readonly class VehicleImageRepresentation
{
    public function __construct(
        public int $id,
        public string $url,
        public bool $isCover,
        public ?string $createdAt,
    ) {}

    /** @return array{id: int, url: string, is_cover: bool, created_at: string|null} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'is_cover' => $this->isCover,
            'created_at' => $this->createdAt,
        ];
    }
}
