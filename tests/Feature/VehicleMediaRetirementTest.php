<?php

namespace Tests\Feature;

use App\Modules\Vehicles\Application\Command\ReconcileVehicleMediaHandler;
use App\Modules\Vehicles\Application\Data\CleanupPolicy;
use App\Modules\Vehicles\Application\Data\CleanupRequest;
use App\Modules\Vehicles\Application\Port\CleanupOutbox;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\MediaCleanupTaskRecord as MediaCleanupTask;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\VehicleRecord as Vehicle;
use App\Modules\Vehicles\Infrastructure\Queue\CleanupVehicleMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class VehicleMediaRetirementTest extends TestCase
{
    use RefreshDatabase;

    public function test_retirement_records_and_dispatches_cleanup_after_commit(): void
    {
        Queue::fake();

        DB::transaction(function (): void {
            app(CleanupOutbox::class)->record(new CleanupRequest(['vehicles/1/obsolete.png'], null));
        });

        $task = MediaCleanupTask::query()->sole();

        $this->assertSame(['vehicles/1/obsolete.png'], $task->paths);
        $this->assertNotNull($task->last_dispatched_at);
        Queue::assertPushed(CleanupVehicleMedia::class, fn (CleanupVehicleMedia $job): bool => $job->taskId === $task->getKey());
    }

    public function test_retirement_does_not_persist_or_dispatch_when_the_transaction_rolls_back(): void
    {
        Queue::fake();

        try {
            DB::transaction(function (): void {
                app(CleanupOutbox::class)->record(new CleanupRequest(['vehicles/1/obsolete.png'], null));

                throw new RuntimeException('Rollback media retirement.');
            });
        } catch (RuntimeException) {
        }

        $this->assertDatabaseCount('media_cleanup_tasks', 0);
        Queue::assertNothingPushed();
    }

    public function test_reconciliation_dispatches_only_unfinished_overdue_tasks(): void
    {
        Queue::fake();
        $now = Carbon::parse('2026-09-04 12:00:00');
        Carbon::setTestNow($now);

        try {
            $overdue = MediaCleanupTask::query()->create([
                'paths' => ['vehicles/1/overdue.png'],
                'last_dispatched_at' => $now->copy()->subMinutes(CleanupPolicy::RECONCILIATION_INTERVAL_MINUTES + 1),
            ]);
            MediaCleanupTask::query()->create([
                'paths' => ['vehicles/1/recent.png'],
                'last_dispatched_at' => $now->copy()->subMinutes(CleanupPolicy::RECONCILIATION_INTERVAL_MINUTES - 1),
            ]);
            MediaCleanupTask::query()->create([
                'paths' => ['vehicles/1/completed.png'],
                'completed_at' => $now->copy()->subDay(),
            ]);

            app(ReconcileVehicleMediaHandler::class)->handle();

            $this->assertSame($now->toDateTimeString(), $overdue->refresh()->last_dispatched_at?->toDateTimeString());
            Queue::assertPushed(CleanupVehicleMedia::class, fn (CleanupVehicleMedia $job): bool => $job->taskId === $overdue->getKey());
            Queue::assertPushed(CleanupVehicleMedia::class, 1);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_reconciliation_removes_only_old_unreferenced_public_media(): void
    {
        Storage::fake('public');
        $disk = Storage::disk('public');
        $vehicle = Vehicle::factory()->create();
        $referenced = "vehicles/{$vehicle->id}/referenced.png";
        $oldOrphan = "vehicles/{$vehicle->id}/old-orphan.png";
        $recentOrphan = "vehicles/{$vehicle->id}/recent-orphan.png";

        $disk->put($referenced, 'referenced');
        $disk->put($oldOrphan, 'old orphan');
        $disk->put($recentOrphan, 'recent orphan');
        $vehicle->images()->create(['path' => $referenced]);
        \touch($disk->path($oldOrphan), now()->subHour()->subSecond()->getTimestamp());

        app(ReconcileVehicleMediaHandler::class)->handle();

        $disk->assertExists($referenced);
        $disk->assertMissing($oldOrphan);
        $disk->assertExists($recentOrphan);
    }
}
