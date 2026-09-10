<?php

namespace App\Modules\Vehicles\Infrastructure\Persistence\Eloquent;

use App\Modules\Vehicles\Application\Data\UserSummaryData;
use App\Modules\Vehicles\Application\Data\VehicleData;
use App\Modules\Vehicles\Application\Data\VehicleDetailsData;
use App\Modules\Vehicles\Application\Data\VehicleImageData;
use App\Modules\Vehicles\Domain\Enum\FuelType;
use App\Modules\Vehicles\Domain\Enum\Transmission;
use App\Modules\Vehicles\Domain\Model\Vehicle;
use App\Modules\Vehicles\Domain\Model\VehicleDetails;
use App\Modules\Vehicles\Domain\Model\VehicleImage;
use App\Modules\Vehicles\Domain\ValueObject\Chassis;
use App\Modules\Vehicles\Domain\ValueObject\Mileage;
use App\Modules\Vehicles\Domain\ValueObject\Money;
use App\Modules\Vehicles\Domain\ValueObject\Plate;
use App\Modules\Vehicles\Domain\ValueObject\VehicleId;
use App\Modules\Vehicles\Domain\ValueObject\VehicleImageId;
use App\Modules\Vehicles\Domain\ValueObject\VehicleVersion;
use DateTimeImmutable;

final class VehicleMapper
{
    public function toDomain(VehicleRecord $record): Vehicle
    {
        $images = $record->images
            ->sortBy('id')
            ->map(fn (VehicleImageRecord $image): VehicleImage => new VehicleImage(
                id: new VehicleImageId((int) $image->getKey()),
                path: $image->path,
                isCover: $image->is_cover,
                createdAt: new DateTimeImmutable($image->created_at?->toISOString() ?? 'now'),
            ))
            ->values()
            ->all();

        return new Vehicle(
            id: new VehicleId((int) $record->getKey()),
            ownerId: $record->user_id,
            details: new VehicleDetails(
                plate: new Plate($record->placa),
                chassis: new Chassis($record->chassi),
                brand: $record->marca,
                model: $record->modelo,
                trim: $record->versao,
                salePrice: Money::fromDecimal($record->valor_venda),
                color: $record->cor,
                mileage: new Mileage($record->km),
                transmission: Transmission::from($record->cambio->value),
                fuelType: FuelType::from($record->combustivel->value),
            ),
            version: new VehicleVersion($record->lock_version),
            updatedBy: $record->updated_by,
            updatedAt: new DateTimeImmutable($record->updated_at?->toISOString() ?? 'now'),
            images: $images,
        );
    }

    public function toData(VehicleRecord $record): VehicleData
    {
        $images = $record->images
            ->sort(static fn (VehicleImageRecord $left, VehicleImageRecord $right): int => [$right->is_cover, $left->id] <=> [$left->is_cover, $right->id])
            ->map($this->toImageData(...))
            ->values()
            ->all();
        $cover = $record->coverImage;

        return new VehicleData(
            id: (int) $record->getKey(),
            details: new VehicleDetailsData(
                plate: $record->placa,
                chassis: $record->chassi,
                brand: $record->marca,
                model: $record->modelo,
                trim: $record->versao,
                salePrice: $record->valor_venda,
                color: $record->cor,
                mileage: $record->km,
                transmission: $record->cambio->value,
                fuelType: $record->combustivel->value,
            ),
            version: $record->lock_version,
            owner: new UserSummaryData((int) $record->owner->getKey(), $record->owner->name),
            coverImage: $cover instanceof VehicleImageRecord ? $this->toImageData($cover) : null,
            images: $images,
            creator: new UserSummaryData((int) $record->creator->getKey(), $record->creator->name),
            updater: new UserSummaryData((int) $record->updater->getKey(), $record->updater->name),
            createdAt: $record->created_at?->toISOString() ?? '',
            updatedAt: $record->updated_at?->toISOString() ?? '',
        );
    }

    public function apply(Vehicle $vehicle, VehicleRecord $record): void
    {
        $details = $vehicle->details();
        $record->forceFill([
            'placa' => $details->plate->value,
            'chassi' => $details->chassis->value,
            'marca' => $details->brand,
            'modelo' => $details->model,
            'versao' => $details->trim,
            'valor_venda' => $details->salePrice->decimal(),
            'cor' => $details->color,
            'km' => $details->mileage->kilometres,
            'cambio' => $details->transmission->value,
            'combustivel' => $details->fuelType->value,
            'updated_by' => $vehicle->updatedBy(),
            'updated_at' => $vehicle->updatedAt(),
            'lock_version' => $vehicle->version()->value,
        ]);
    }

    public function toImageData(VehicleImageRecord $image): VehicleImageData
    {
        return new VehicleImageData(
            id: (int) $image->getKey(),
            path: $image->path,
            isCover: $image->is_cover,
            createdAt: $image->created_at?->toISOString() ?? '',
        );
    }
}
