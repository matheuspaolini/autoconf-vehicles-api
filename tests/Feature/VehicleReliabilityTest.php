<?php

namespace Tests\Feature;

use App\Jobs\CleanupVehicleMedia;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleReliabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_updates_require_a_current_etag(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();

        $this->actingAs($owner)
            ->patchJson("/api/vehicles/{$vehicle->id}", $this->payload())
            ->assertStatus(428);

        $this->actingAs($owner)
            ->withHeader('If-Match', $this->etag($vehicle))
            ->patchJson("/api/vehicles/{$vehicle->id}", $this->payload(['marca' => 'Ford']))
            ->assertOk()
            ->assertHeader('ETag', "\"vehicle-{$vehicle->id}-v2\"");

        $this->actingAs($owner)
            ->withHeader('If-Match', $this->etag($vehicle))
            ->patchJson("/api/vehicles/{$vehicle->id}", $this->payload(['marca' => 'Toyota']))
            ->assertStatus(412);
    }

    public function test_completed_uploads_replay_before_etag_validation(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();
        $key = (string) str()->uuid();
        $file = UploadedFile::fake()->createWithContent('car.png', file_get_contents(database_path('seeders/assets/vehicle-placeholder.png')));

        $headers = ['If-Match' => $this->etag($vehicle), 'Idempotency-Key' => $key];
        $first = $this->actingAs($owner)->withHeaders($headers)
            ->post("/api/vehicles/{$vehicle->id}/images", ['files' => [$file]])
            ->assertCreated();

        $replay = $this->actingAs($owner)->withHeaders($headers)
            ->post("/api/vehicles/{$vehicle->id}/images", ['files' => [$file]])
            ->assertCreated();

        $this->assertSame($first->json('data'), $replay->json('data'));
        $this->assertSame(1, $vehicle->images()->count());
    }

    public function test_vehicle_deletion_creates_and_dispatches_a_cleanup_task(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();
        $vehicle->images()->create(['path' => "vehicles/{$vehicle->id}/cover.png"]);

        $this->actingAs($owner)->withHeader('If-Match', $this->etag($vehicle))
            ->deleteJson("/api/vehicles/{$vehicle->id}")
            ->assertNoContent();

        $this->assertDatabaseHas('media_cleanup_tasks', ['directory' => "vehicles/{$vehicle->id}"]);
        Queue::assertPushed(CleanupVehicleMedia::class);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_replace([
            'placa' => 'ABC1D23', 'chassi' => '9BWZZZ377VT004251', 'marca' => 'Chevrolet',
            'modelo' => 'Onix', 'versao' => 'LT', 'valor_venda' => '78900.00', 'cor' => 'Prata',
            'km' => 100, 'cambio' => 'automatico', 'combustivel' => 'flex',
        ], $overrides);
    }

    private function etag(Vehicle $vehicle): string
    {
        return "\"vehicle-{$vehicle->id}-v{$vehicle->lock_version}\"";
    }
}
