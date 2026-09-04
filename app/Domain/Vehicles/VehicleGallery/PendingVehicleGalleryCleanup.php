<?php

namespace App\Domain\Vehicles\VehicleGallery;

use Illuminate\Contracts\Filesystem\Filesystem;
use Psr\Log\LoggerInterface;

final readonly class PendingVehicleGalleryCleanup
{
    /**
     * @param  list<string>  $paths
     */
    public function __construct(
        private int $vehicleId,
        private array $paths,
        private Filesystem $disk,
        private LoggerInterface $logger,
    ) {}

    public function execute(): void
    {
        foreach ($this->paths as $path) {
            if (! $this->disk->delete($path)) {
                $this->logger->warning(
                    'Vehicle image file could not be deleted',
                    [
                        'vehicle_id' => $this->vehicleId,
                        'path' => $path,
                    ],
                );
            }
        }

        $directory = "vehicles/{$this->vehicleId}";

        if (! $this->disk->deleteDirectory($directory)) {
            $this->logger->warning(
                'Vehicle image directory could not be deleted',
                [
                    'vehicle_id' => $this->vehicleId,
                    'directory' => $directory,
                ],
            );
        }
    }
}
