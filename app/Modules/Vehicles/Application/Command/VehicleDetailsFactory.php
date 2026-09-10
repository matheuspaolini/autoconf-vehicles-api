<?php

namespace App\Modules\Vehicles\Application\Command;

use App\Modules\Vehicles\Application\Data\VehicleDetailsData;
use App\Modules\Vehicles\Domain\Enum\FuelType;
use App\Modules\Vehicles\Domain\Enum\Transmission;
use App\Modules\Vehicles\Domain\Model\VehicleDetails;
use App\Modules\Vehicles\Domain\ValueObject\Chassis;
use App\Modules\Vehicles\Domain\ValueObject\Mileage;
use App\Modules\Vehicles\Domain\ValueObject\Money;
use App\Modules\Vehicles\Domain\ValueObject\Plate;

final class VehicleDetailsFactory
{
    public function fromData(VehicleDetailsData $data): VehicleDetails
    {
        return new VehicleDetails(
            plate: new Plate($data->plate),
            chassis: new Chassis($data->chassis),
            brand: \trim($data->brand),
            model: \trim($data->model),
            trim: \trim($data->trim),
            salePrice: Money::fromDecimal($data->salePrice),
            color: \trim($data->color),
            mileage: new Mileage($data->mileage),
            transmission: Transmission::from($data->transmission),
            fuelType: FuelType::from($data->fuelType),
        );
    }
}
