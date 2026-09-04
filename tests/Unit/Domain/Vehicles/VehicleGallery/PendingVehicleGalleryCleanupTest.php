<?php

namespace Tests\Unit\Domain\Vehicles\VehicleGallery;

use App\Domain\Vehicles\VehicleGallery\PendingVehicleGalleryCleanup;
use Illuminate\Contracts\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PendingVehicleGalleryCleanupTest extends TestCase
{
    public function test_it_deletes_each_file_and_its_vehicle_directory(): void
    {
        $disk = $this->createMock(Filesystem::class);
        $logger = $this->createMock(LoggerInterface::class);

        $disk->expects($this->once())
            ->method('delete')
            ->with('vehicles/42/cover.png')
            ->willReturn(true);

        $disk->expects($this->once())
            ->method('deleteDirectory')
            ->with('vehicles/42')
            ->willReturn(true);

        $logger->expects($this->never())->method('warning');

        (new PendingVehicleGalleryCleanup(42, ['vehicles/42/cover.png'], $disk, $logger))->execute();
    }

    public function test_it_logs_storage_failures_with_the_affected_context(): void
    {
        $disk = $this->createMock(Filesystem::class);
        $logger = $this->createMock(LoggerInterface::class);
        $warnings = [];

        $disk->expects($this->once())
            ->method('delete')
            ->with('vehicles/42/cover.png')
            ->willReturn(false);

        $disk->expects($this->once())
            ->method('deleteDirectory')
            ->with('vehicles/42')
            ->willReturn(false);

        $logger->expects($this->exactly(2))
            ->method('warning')
            ->willReturnCallback(function (string $message, array $context) use (&$warnings): void {
                $warnings[] = [$message, $context];
            });

        (new PendingVehicleGalleryCleanup(42, ['vehicles/42/cover.png'], $disk, $logger))->execute();

        $this->assertSame([
            ['Vehicle image file could not be deleted', ['vehicle_id' => 42, 'path' => 'vehicles/42/cover.png']],
            ['Vehicle image directory could not be deleted', ['vehicle_id' => 42, 'directory' => 'vehicles/42']],
        ], $warnings);
    }
}
