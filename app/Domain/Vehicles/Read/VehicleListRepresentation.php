<?php

namespace App\Domain\Vehicles\Read;

use App\Enums\FuelType;
use App\Enums\Transmission;

final readonly class VehicleListRepresentation
{
    public function __construct(
        public int $id,
        public int $lockVersion,
        public string $placa,
        public string $chassi,
        public string $marca,
        public string $modelo,
        public string $versao,
        public string $valorVenda,
        public string $cor,
        public int $km,
        public Transmission $cambio,
        public FuelType $combustivel,
        public int $ownerId,
        public string $ownerName,
        public ?VehicleImageRepresentation $coverImage,
        public bool $canUpdate,
        public bool $canDelete,
        public bool $canManageImages,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    /**
     * @return array{id: int, placa: string, chassi: string, marca: string, modelo: string, versao: string, valor_venda: string, cor: string, km: int, cambio: 'manual'|'automatico', combustivel: 'gasolina'|'alcool'|'flex'|'diesel'|'hibrido'|'eletrico', owner: array{id: int, name: string}, cover_image: array{id: int, url: string, is_cover: bool, created_at: string|null}|null, permissions: array{update: bool, delete: bool, manage_images: bool}, created_at: string|null, updated_at: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'placa' => $this->placa,
            'chassi' => $this->chassi,
            'marca' => $this->marca,
            'modelo' => $this->modelo,
            'versao' => $this->versao,
            'valor_venda' => $this->valorVenda,
            'cor' => $this->cor,
            'km' => $this->km,
            'cambio' => $this->cambio->value,
            'combustivel' => $this->combustivel->value,
            'owner' => ['id' => $this->ownerId, 'name' => $this->ownerName],
            'cover_image' => $this->coverImage?->toArray(),
            'permissions' => [
                'update' => $this->canUpdate,
                'delete' => $this->canDelete,
                'manage_images' => $this->canManageImages,
            ],
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
