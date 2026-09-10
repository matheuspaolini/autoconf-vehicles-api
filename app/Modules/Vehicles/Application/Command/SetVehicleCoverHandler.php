<?php

namespace App\Modules\Vehicles\Application\Command;

use App\Modules\Vehicles\Application\Data\VehicleImageData;
use App\Modules\Vehicles\Application\Port\Clock;
use App\Modules\Vehicles\Application\Port\TransactionRunner;
use App\Modules\Vehicles\Application\Port\VehicleReadGateway;
use App\Modules\Vehicles\Application\Port\VehicleWriteGateway;
use App\Modules\Vehicles\Application\Result\SetVehicleCoverError;
use App\Modules\Vehicles\Application\Result\SetVehicleCoverResult;
use App\Modules\Vehicles\Domain\Model\VehicleError;
use App\Modules\Vehicles\Domain\ValueObject\VehicleId;
use App\Modules\Vehicles\Domain\ValueObject\VehicleImageId;
use App\Modules\Vehicles\Domain\ValueObject\VehicleVersion;

final readonly class SetVehicleCoverHandler
{
    public function __construct(
        private VehicleWriteGateway $vehicles,
        private VehicleReadGateway $reads,
        private TransactionRunner $transactions,
        private Clock $clock,
    ) {}

    public function handle(SetVehicleCover $command): SetVehicleCoverResult
    {
        $vehicleId = new VehicleId($command->vehicleId);

        return $this->transactions->run(function () use ($command, $vehicleId): SetVehicleCoverResult {
            $vehicle = $this->vehicles->getForUpdate($vehicleId);

            if ($vehicle === null) {
                return SetVehicleCoverResult::failure(SetVehicleCoverError::NotFound);
            }

            $outcome = $vehicle->selectCover(
                new VehicleImageId($command->imageId),
                $command->actor,
                new VehicleVersion($command->expectedVersion),
                $this->clock->now(),
            );

            if ($outcome->failed()) {
                return SetVehicleCoverResult::failure($this->mapError($outcome->error));
            }

            $this->vehicles->update($vehicle);
            $data = $this->reads->detail($vehicleId);
            $image = $data === null ? null : $this->findImage($data->images, $command->imageId);

            if ($image === null) {
                throw new \LogicException('The selected cover projection is missing.');
            }

            return SetVehicleCoverResult::success($image, $vehicle->version()->value);
        });
    }

    private function mapError(?VehicleError $error): SetVehicleCoverError
    {
        return match ($error) {
            VehicleError::Forbidden => SetVehicleCoverError::Forbidden,
            VehicleError::VersionConflict => SetVehicleCoverError::VersionConflict,
            VehicleError::ImageNotFound => SetVehicleCoverError::ImageNotFound,
            default => throw new \LogicException('Unexpected cover-selection error.'),
        };
    }

    private function findImage(array $images, int $imageId): ?VehicleImageData
    {
        foreach ($images as $image) {
            if ($image->id === $imageId) {
                return $image;
            }
        }

        return null;
    }
}
