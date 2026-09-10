<?php

namespace App\Modules\Vehicles\Application\Command;

use App\Modules\Vehicles\Application\Data\CleanupRequest;
use App\Modules\Vehicles\Application\Port\CleanupOutbox;
use App\Modules\Vehicles\Application\Port\Clock;
use App\Modules\Vehicles\Application\Port\TransactionRunner;
use App\Modules\Vehicles\Application\Port\VehicleWriteGateway;
use App\Modules\Vehicles\Application\Result\DeleteVehicleImageError;
use App\Modules\Vehicles\Application\Result\DeleteVehicleImageResult;
use App\Modules\Vehicles\Domain\Model\VehicleError;
use App\Modules\Vehicles\Domain\ValueObject\VehicleId;
use App\Modules\Vehicles\Domain\ValueObject\VehicleImageId;
use App\Modules\Vehicles\Domain\ValueObject\VehicleVersion;

final readonly class DeleteVehicleImageHandler
{
    public function __construct(
        private VehicleWriteGateway $vehicles,
        private CleanupOutbox $cleanup,
        private TransactionRunner $transactions,
        private Clock $clock,
    ) {}

    public function handle(DeleteVehicleImage $command): DeleteVehicleImageResult
    {
        $vehicleId = new VehicleId($command->vehicleId);

        return $this->transactions->run(function () use ($command, $vehicleId): DeleteVehicleImageResult {
            $vehicle = $this->vehicles->getForUpdate($vehicleId);

            if ($vehicle === null) {
                return DeleteVehicleImageResult::failure(DeleteVehicleImageError::NotFound);
            }

            $path = $this->imagePath($vehicle->images(), $command->imageId);
            $outcome = $vehicle->removeImage(
                new VehicleImageId($command->imageId),
                $command->actor,
                new VehicleVersion($command->expectedVersion),
                $this->clock->now(),
            );

            if ($outcome->failed()) {
                return DeleteVehicleImageResult::failure($this->mapError($outcome->error));
            }

            $this->vehicles->update($vehicle);
            $this->cleanup->record(new CleanupRequest([$path], null));

            return DeleteVehicleImageResult::success();
        });
    }

    private function imagePath(array $images, int $imageId): string
    {
        foreach ($images as $image) {
            if ($image->id?->value === $imageId) {
                return $image->path;
            }
        }

        return '';
    }

    private function mapError(?VehicleError $error): DeleteVehicleImageError
    {
        return match ($error) {
            VehicleError::Forbidden => DeleteVehicleImageError::Forbidden,
            VehicleError::VersionConflict => DeleteVehicleImageError::VersionConflict,
            VehicleError::ImageNotFound => DeleteVehicleImageError::ImageNotFound,
            default => throw new \LogicException('Unexpected image-deletion error.'),
        };
    }
}
