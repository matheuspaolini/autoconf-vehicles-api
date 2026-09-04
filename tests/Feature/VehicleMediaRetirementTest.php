<?php

namespace Tests\Feature;

use App\Domain\Vehicles\VehicleMediaRetirement;
use App\Jobs\CleanupVehicleMedia;
use App\Models\MediaCleanupTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class VehicleMediaRetirementTest extends TestCase
{
    use RefreshDatabase;

    public function test_retirement_records_and_dispatches_cleanup_after_commit(): void
    {
        Queue::fake();

        DB::transaction(function (): void {
            app(VehicleMediaRetirement::class)->retire(['vehicles/1/obsolete.png'], null);
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
                app(VehicleMediaRetirement::class)->retire(['vehicles/1/obsolete.png'], null);

                throw new RuntimeException('Rollback media retirement.');
            });
        } catch (RuntimeException) {
            // The cleanup task and after-commit dispatch must be discarded.
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
                'last_dispatched_at' => $now->copy()->subMinutes(VehicleMediaRetirement::RECONCILIATION_INTERVAL_MINUTES + 1),
            ]);
            MediaCleanupTask::query()->create([
                'paths' => ['vehicles/1/recent.png'],
                'last_dispatched_at' => $now->copy()->subMinutes(VehicleMediaRetirement::RECONCILIATION_INTERVAL_MINUTES - 1),
            ]);
            MediaCleanupTask::query()->create([
                'paths' => ['vehicles/1/completed.png'],
                'completed_at' => $now->copy()->subDay(),
            ]);

            app(VehicleMediaRetirement::class)->reconcile();

            $this->assertSame($now->toDateTimeString(), $overdue->refresh()->last_dispatched_at?->toDateTimeString());
            Queue::assertPushed(CleanupVehicleMedia::class, fn (CleanupVehicleMedia $job): bool => $job->taskId === $overdue->getKey());
            Queue::assertPushed(CleanupVehicleMedia::class, 1);
        } finally {
            Carbon::setTestNow();
        }
    }
}
