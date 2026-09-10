<?php

namespace Tests\Integration\Modules\Vehicles\Queue;

use App\Modules\Vehicles\Application\Command\CompleteVehicleMediaCleanupHandler;
use App\Modules\Vehicles\Application\Data\CleanupRequest;
use App\Modules\Vehicles\Application\Data\StoredImage;
use App\Modules\Vehicles\Application\Port\CleanupOutbox;
use App\Modules\Vehicles\Application\Port\MediaStore;
use App\Modules\Vehicles\Application\Port\UploadSource;
use App\Modules\Vehicles\Domain\ValueObject\VehicleId;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\EloquentCleanupStore;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\MediaCleanupTaskRecord;
use App\Modules\Vehicles\Infrastructure\Queue\CleanupVehicleMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

final class VehicleMediaCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_outbox_dispatches_once_and_only_after_commit(): void
    {
        Queue::fake();

        DB::transaction(function (): void {
            app(CleanupOutbox::class)->record(new CleanupRequest(['vehicles/1/old.png'], null));
            Queue::assertNothingPushed();
        });

        Queue::assertPushed(CleanupVehicleMedia::class, 1);
        self::assertNotNull(MediaCleanupTaskRecord::query()->sole()->last_dispatched_at);
    }

    public function test_cleanup_failure_is_recorded_and_rethrown(): void
    {
        $task = MediaCleanupTaskRecord::query()->create(['paths' => ['vehicles/1/old.png']]);
        $media = new class implements MediaStore
        {
            public function store(VehicleId $vehicleId, UploadSource $source): StoredImage
            {
                throw new RuntimeException('Not used.');
            }

            public function delete(array $paths): void
            {
                throw new RuntimeException('Storage unavailable.');
            }

            public function deleteDirectory(string $directory): void {}
        };
        $handler = new CompleteVehicleMediaCleanupHandler(new EloquentCleanupStore, $media);

        try {
            $handler->handle((int) $task->getKey());
            self::fail('The storage failure should be rethrown.');
        } catch (RuntimeException $exception) {
            self::assertSame('Storage unavailable.', $exception->getMessage());
        }

        $task->refresh();
        self::assertSame(1, $task->attempts);
        self::assertSame('Storage unavailable.', $task->last_error);
        self::assertNull($task->completed_at);
    }
}
