<?php

namespace App\Domain\Vehicles\Read;

final readonly class VehicleDetailRepresentation
{
    public function __construct(
        public VehicleListRepresentation $vehicle,
        public VehicleAuditRepresentation $audit,
    ) {}

    /**
     * @return array{id: int, placa: string, chassi: string, marca: string, modelo: string, versao: string, valor_venda: string, cor: string, km: int, cambio: 'manual'|'automatico', combustivel: 'gasolina'|'alcool'|'flex'|'diesel'|'hibrido'|'eletrico', owner: array{id: int, name: string}, cover_image: array{id: int, url: string, is_cover: bool, created_at: string|null}|null, permissions: array{update: bool, delete: bool, manage_images: bool}, created_at: string|null, updated_at: string|null, audit: array{created_at: string|null, created_by: array{id: int, name: string}, updated_at: string|null, updated_by: array{id: int, name: string}}}
     */
    public function toArray(): array
    {
        return [...$this->vehicle->toArray(), 'audit' => $this->audit->toArray()];
    }
}
