<?php

namespace App\Modules\Vehicles\Application\Command;

use App\Modules\Vehicles\Application\Exception\DuplicateVehicleValue;
use App\Modules\Vehicles\Application\Port\Clock;
use App\Modules\Vehicles\Application\Port\TransactionRunner;
use App\Modules\Vehicles\Application\Port\VehicleReadGateway;
use App\Modules\Vehicles\Application\Port\VehicleWriteGateway;
use App\Modules\Vehicles\Application\Result\CreateVehicleError;
use App\Modules\Vehicles\Application\Result\CreateVehicleResult;
use App\Modules\Vehicles\Domain\Exception\InvalidVehicleValue;
use App\Modules\Vehicles\Domain\Model\Vehicle;
use App\Modules\Vehicles\Domain\ValueObject\VehicleVersion;

final readonly class CreateVehicleHandler
{
    public function __construct(
        private VehicleWriteGateway $vehicles,
        private VehicleReadGateway $reads,
        private TransactionRunner $transactions,
        private Clock $clock,
        private VehicleDetailsFactory $detailsFactory,
    ) {}

    public function handle(CreateVehicle $command): CreateVehicleResult
    {
        try {
            $details = $this->detailsFactory->fromData($command->details);
        } catch (InvalidVehicleValue|\ValueError) {
            return CreateVehicleResult::failure(CreateVehicleError::InvalidInput);
        }

        return $this->transactions->run(function () use ($command, $details): CreateVehicleResult {
            $id = $this->vehicles->nextIdentity();
            $now = $this->clock->now();
            $vehicle = new Vehicle(
                id: $id,
                ownerId: $command->actor->userId,
                details: $details,
                version: new VehicleVersion(1),
                updatedBy: $command->actor->userId,
                updatedAt: $now,
            );

            try {
                $this->vehicles->insert($vehicle, $command->actor->userId);
            } catch (DuplicateVehicleValue $exception) {
                $error = $exception->field === 'placa'
                    ? CreateVehicleError::DuplicatePlate
                    : CreateVehicleError::DuplicateChassis;

                return CreateVehicleResult::failure($error, [
                    $exception->field => ['The '.$exception->field.' has already been taken.'],
                ]);
            }

            return CreateVehicleResult::success(
                $this->reads->detail($id)
                    ?? throw new \LogicException('The created Vehicle projection is missing.'),
            );
        });
    }
}
