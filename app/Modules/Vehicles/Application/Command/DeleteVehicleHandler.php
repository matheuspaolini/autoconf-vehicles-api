<?php

namespace App\Modules\Vehicles\Application\Command;

use App\Modules\Vehicles\Application\Data\CleanupRequest;
use App\Modules\Vehicles\Application\Port\CleanupOutbox;
use App\Modules\Vehicles\Application\Port\Clock;
use App\Modules\Vehicles\Application\Port\TransactionRunner;
use App\Modules\Vehicles\Application\Port\VehicleWriteGateway;
use App\Modules\Vehicles\Application\Result\DeleteVehicleError;
use App\Modules\Vehicles\Application\Result\DeleteVehicleResult;
use App\Modules\Vehicles\Domain\Model\VehicleError;
use App\Modules\Vehicles\Domain\ValueObject\VehicleId;
use App\Modules\Vehicles\Domain\ValueObject\VehicleVersion;

final readonly class DeleteVehicleHandler
{
    public function __construct(
        private VehicleWriteGateway $vehicles,
        private CleanupOutbox $cleanup,
        private TransactionRunner $transactions,
        private Clock $clock,
    ) {}

    public function handle(DeleteVehicle $command): DeleteVehicleResult
    {
        $id = new VehicleId($command->vehicleId);

        return $this->transactions->run(function () use ($command, $id): DeleteVehicleResult {
            $vehicle = $this->vehicles->getForUpdate($id);

            if ($vehicle === null) {
                return DeleteVehicleResult::failure(DeleteVehicleError::NotFound);
            }

            $outcome = $vehicle->retire(
                $command->actor,
                new VehicleVersion($command->expectedVersion),
                $this->clock->now(),
            );

            if ($outcome->error === VehicleError::Forbidden) {
                return DeleteVehicleResult::failure(DeleteVehicleError::Forbidden);
            }

            if ($outcome->error === VehicleError::VersionConflict) {
                return DeleteVehicleResult::failure(DeleteVehicleError::VersionConflict);
            }

            $paths = \array_map(static fn ($image): string => $image->path, $vehicle->images());
            $this->cleanup->record(new CleanupRequest($paths, "vehicles/{$id->value}"));
            $this->vehicles->delete($vehicle);

            return DeleteVehicleResult::success();
        });
    }
}
