<?php

namespace App\Domain\Vehicles\Read;

final readonly class VehicleAuditRepresentation
{
    public function __construct(
        public ?string $createdAt,
        public int $createdById,
        public string $createdByName,
        public ?string $updatedAt,
        public int $updatedById,
        public string $updatedByName,
    ) {}

    /**
     * @return array{created_at: string|null, created_by: array{id: int, name: string}, updated_at: string|null, updated_by: array{id: int, name: string}}
     */
    public function toArray(): array
    {
        return [
            'created_at' => $this->createdAt,
            'created_by' => ['id' => $this->createdById, 'name' => $this->createdByName],
            'updated_at' => $this->updatedAt,
            'updated_by' => ['id' => $this->updatedById, 'name' => $this->updatedByName],
        ];
    }
}
