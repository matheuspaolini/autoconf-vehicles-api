<?php

namespace App\Modules\Vehicles\Application\Command;

use App\Modules\Vehicles\Application\Exception\DuplicateVehicleValue;
use App\Modules\Vehicles\Application\Port\Clock;
use App\Modules\Vehicles\Application\Port\TransactionRunner;
use App\Modules\Vehicles\Application\Port\VehicleReadGateway;
use App\Modules\Vehicles\Application\Port\VehicleWriteGateway;
use App\Modules\Vehicles\Application\Result\UpdateVehicleError;
use App\Modules\Vehicles\Application\Result\UpdateVehicleResult;
use App\Modules\Vehicles\Domain\Exception\InvalidVehicleValue;
use App\Modules\Vehicles\Domain\Model\VehicleError;
use App\Modules\Vehicles\Domain\ValueObject\VehicleId;
use App\Modules\Vehicles\Domain\ValueObject\VehicleVersion;

final readonly class UpdateVehicleHandler
{
    public function __construct(
        private VehicleWriteGateway $vehicles,
        private VehicleReadGateway $reads,
        private TransactionRunner $transactions,
        private Clock $clock,
        private VehicleDetailsFactory $detailsFactory,
    ) {}

    public function handle(UpdateVehicle $command): UpdateVehicleResult
    {
        try {
            $vehicleId = new VehicleId($command->vehicleId);
            $details = $this->detailsFactory->fromData($command->details);
            $expectedVersion = new VehicleVersion($command->expectedVersion);
        } catch (InvalidVehicleValue|\ValueError) {
            return UpdateVehicleResult::failure(UpdateVehicleError::InvalidInput);
        }

        return $this->transactions->run(function () use ($vehicleId, $details, $expectedVersion, $command): UpdateVehicleResult {
            $vehicle = $this->vehicles->getForUpdate($vehicleId);

            if ($vehicle === null) {
                return UpdateVehicleResult::failure(UpdateVehicleError::NotFound);
            }

            $outcome = $vehicle->updateDetails($details, $command->actor, $expectedVersion, $this->clock->now());

            if ($outcome->failed()) {
                return UpdateVehicleResult::failure($this->mapDomainError($outcome->error));
            }

            try {
                $this->vehicles->update($vehicle);
            } catch (DuplicateVehicleValue $exception) {
                $error = $exception->field === 'placa'
                    ? UpdateVehicleError::DuplicatePlate
                    : UpdateVehicleError::DuplicateChassis;

                return UpdateVehicleResult::failure($error, [
                    $exception->field => ['The '.$exception->field.' has already been taken.'],
                ]);
            }

            return UpdateVehicleResult::success(
                $this->reads->detail($vehicleId)
                    ?? throw new \LogicException('The updated Vehicle projection is missing.'),
            );
        });
    }

    private function mapDomainError(?VehicleError $error): UpdateVehicleError
    {
        return match ($error) {
            VehicleError::Forbidden => UpdateVehicleError::Forbidden,
            VehicleError::VersionConflict => UpdateVehicleError::VersionConflict,
            VehicleError::GalleryCapacityExceeded,
            VehicleError::ImageNotFound => throw new \LogicException('Unexpected Vehicle update error.'),
            null => throw new \LogicException('A failed domain outcome must contain an error.'),
        };
    }
}
