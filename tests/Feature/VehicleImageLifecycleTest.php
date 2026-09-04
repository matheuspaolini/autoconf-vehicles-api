<?php

namespace Tests\Feature;

use App\Domain\Vehicles\VehicleGallery\VehicleImageLifecycle;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleImageLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_cover_selection_leaves_exactly_one_cover(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();
        $first = $this->image($vehicle, 'one.png', true);
        $second = $this->image($vehicle, 'two.png');

        $this->actingAs($owner)
            ->patchJson("/api/vehicles/{$vehicle->id}/images/{$second->id}/cover")
            ->assertOk()
            ->assertJsonPath('data.id', $second->id);

        $this->assertSame(1, $vehicle->images()->where('is_cover', true)->count());
        $this->assertTrue($second->refresh()->is_cover);
        $this->assertFalse($first->refresh()->is_cover);
    }

    public function test_uploading_into_an_empty_gallery_creates_one_cover_and_updates_audit_data(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();

        $created = app(VehicleImageLifecycle::class)->upload($vehicle, $owner, [
            UploadedFile::fake()->create('one.png', 20, 'image/png'),
            UploadedFile::fake()->create('two.png', 20, 'image/png'),
        ]);

        $this->assertCount(2, $created);
        $this->assertTrue($created->first()->is_cover);
        $this->assertFalse($created->last()->is_cover);
        $this->assertSame($owner->id, $vehicle->refresh()->updated_by);
        $this->assertValidGallery($vehicle);
    }

    public function test_deleting_a_cover_promotes_the_oldest_remaining_image(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();
        $first = $this->image($vehicle, 'one.png', true);
        $second = $this->image($vehicle, 'two.png');

        $this->actingAs($owner)
            ->patchJson("/api/vehicles/{$vehicle->id}/images/{$second->id}/cover")
            ->assertOk();

        $this->actingAs($owner)
            ->deleteJson("/api/vehicles/{$vehicle->id}/images/{$second->id}")
            ->assertNoContent();

        $this->assertTrue($first->refresh()->is_cover);
        Storage::disk('public')->assertMissing($second->path);
    }

    public function test_deleting_the_last_image_leaves_an_empty_gallery(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();
        $image = $this->image($vehicle, 'only.png', true);

        app(VehicleImageLifecycle::class)->delete($vehicle, $image, $owner);

        $this->assertValidGallery($vehicle);
        $this->assertDatabaseMissing('vehicle_images', ['id' => $image->id]);
    }

    public function test_nested_images_are_scoped_to_the_vehicle(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();
        $otherVehicle = Vehicle::factory()->forOwner($owner)->create();
        $image = $this->image($vehicle, 'only.png', true);

        $this->actingAs($owner)
            ->patchJson("/api/vehicles/{$otherVehicle->id}/images/{$image->id}/cover")
            ->assertNotFound();
    }

    public function test_set_vehicle_cover_rejects_an_image_from_another_vehicle_without_changing_covers(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();
        $otherVehicle = Vehicle::factory()->forOwner($owner)->create();
        $cover = $this->image($vehicle, 'cover.png', true);
        $otherCover = $this->image($otherVehicle, 'other-cover.png', true);

        try {
            app(VehicleImageLifecycle::class)->setCover($vehicle, $otherCover, $owner);
            $this->fail('Expected an image from another vehicle to be rejected.');
        } catch (ModelNotFoundException) {
            // The Action must fail before clearing either vehicle's cover.
        }

        $this->assertTrue($cover->refresh()->is_cover);
        $this->assertTrue($otherCover->refresh()->is_cover);
        $this->assertSame(1, $vehicle->images()->where('is_cover', true)->count());
        $this->assertSame(1, $otherVehicle->images()->where('is_cover', true)->count());
    }

    public function test_vehicle_deletion_cleans_its_image_storage_and_records(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();
        $image = $this->image($vehicle, 'only.png', true);

        $this->actingAs($owner)
            ->deleteJson("/api/vehicles/{$vehicle->id}")
            ->assertNoContent();

        Storage::disk('public')->assertMissing($image->path);
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
        $this->assertDatabaseMissing('vehicle_images', ['id' => $image->id]);
    }

    public function test_upload_rejects_more_than_ten_files(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();
        $files = array_fill(0, 11, UploadedFile::fake()->create('large.png', 20, 'image/png'));

        $this->actingAs($owner)
            ->withHeader('Accept', 'application/json')
            ->post("/api/vehicles/{$vehicle->id}/images", ['files' => $files])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files');
    }

    public function test_upload_rejects_svg_files(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();

        $this->actingAs($owner)
            ->withHeader('Accept', 'application/json')
            ->post("/api/vehicles/{$vehicle->id}/images", [
                'files' => [UploadedFile::fake()->create('drawing.svg', 20, 'image/svg+xml')],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files.0');
    }

    public function test_direct_mass_assignment_cannot_override_image_ownership_or_cover(): void
    {
        $vehicle = Vehicle::factory()->create();
        $otherVehicle = Vehicle::factory()->create();

        $image = new VehicleImage([
            'vehicle_id' => $otherVehicle->id,
            'path' => "vehicles/{$vehicle->id}/untrusted.png",
            'is_cover' => true,
        ]);
        $vehicle->images()->save($image);

        $this->assertSame($vehicle->id, $image->refresh()->vehicle_id);
        $this->assertFalse($image->refresh()->is_cover);
    }

    public function test_database_rejects_a_second_cover_for_one_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->image($vehicle, 'one.png', true);

        $this->expectException(QueryException::class);
        VehicleImage::factory()->for($vehicle)->cover()->create();
    }

    public function test_migration_repairs_a_gallery_without_a_cover(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->repairLegacyGallery($vehicle, [false, false]);

        $this->assertSame(
            [true, false],
            $vehicle->images()->orderBy('id')->pluck('is_cover')->map(fn (int $cover): bool => (bool) $cover)->all(),
        );
    }

    public function test_migration_repairs_multiple_covers_using_the_oldest_image(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->repairLegacyGallery($vehicle, [true, true, false]);

        $this->assertSame(
            [true, false, false],
            $vehicle->images()->orderBy('id')->pluck('is_cover')->map(fn (int $cover): bool => (bool) $cover)->all(),
        );
    }

    private function assertValidGallery(Vehicle $vehicle): void
    {
        $imageCount = $vehicle->images()->count();
        $coverCount = $vehicle->images()->where('is_cover', true)->count();

        $this->assertSame($imageCount === 0 ? 0 : 1, $coverCount);
    }

    /**
     * @param  list<bool>  $covers
     */
    private function repairLegacyGallery(Vehicle $vehicle, array $covers): void
    {
        $migration = require database_path('migrations/2026_09_04_000003_enforce_one_cover_per_vehicle.php');
        $migration->down();

        foreach ($covers as $index => $cover) {
            DB::table('vehicle_images')->insert([
                'vehicle_id' => $vehicle->id,
                'path' => "vehicles/{$vehicle->id}/legacy-{$index}.png",
                'is_cover' => $cover,
            ]);
        }

        $migration->up();
    }

    private function image(Vehicle $vehicle, string $name, bool $cover = false): VehicleImage
    {
        $path = "vehicles/{$vehicle->id}/{$name}";
        Storage::disk('public')->put($path, file_get_contents(database_path('seeders/assets/vehicle-placeholder.png')));

        $image = $vehicle->images()->create(['path' => $path]);

        if ($cover) {
            $image->forceFill(['is_cover' => true])->save();
        }

        return $image;
    }
}
