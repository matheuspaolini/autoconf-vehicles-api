<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleImageLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_cover_change_and_deletion_preserve_the_cover_invariant(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();
        $first = $this->image($vehicle, 'one.png', true);
        $second = $this->image($vehicle, 'two.png');

        $this->actingAs($owner)->patchJson("/api/vehicles/{$vehicle->id}/images/{$second->id}/cover")->assertOk()->assertJsonPath('data.id', $second->id);
        $this->assertSame(1, $vehicle->images()->where('is_cover', true)->count());
        $this->assertTrue($second->refresh()->is_cover);
        $this->assertFalse($first->refresh()->is_cover);

        $this->actingAs($owner)->deleteJson("/api/vehicles/{$vehicle->id}/images/{$second->id}")->assertNoContent();
        $this->assertTrue($first->refresh()->is_cover);
        Storage::disk('public')->assertMissing($second->path);
    }

    public function test_nested_images_are_scoped_and_vehicle_deletion_cleans_storage(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();
        $other = Vehicle::factory()->forOwner($owner)->create();
        $image = $this->image($vehicle, 'only.png', true);

        $this->actingAs($owner)->patchJson("/api/vehicles/{$other->id}/images/{$image->id}/cover")->assertNotFound();
        $this->actingAs($owner)->deleteJson("/api/vehicles/{$vehicle->id}")->assertNoContent();

        Storage::disk('public')->assertMissing($image->path);
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
        $this->assertDatabaseMissing('vehicle_images', ['id' => $image->id]);
    }

    public function test_upload_rejects_more_than_ten_files_and_svg_files(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();
        $files = array_fill(0, 11, UploadedFile::fake()->create('large.png', 20, 'image/png'));

        $this->actingAs($owner)->withHeader('Accept', 'application/json')->post("/api/vehicles/{$vehicle->id}/images", ['files' => $files])->assertUnprocessable()->assertJsonValidationErrors('files');
        $this->actingAs($owner)->withHeader('Accept', 'application/json')->post("/api/vehicles/{$vehicle->id}/images", ['files' => [UploadedFile::fake()->create('drawing.svg', 20, 'image/svg+xml')]])->assertUnprocessable()->assertJsonValidationErrors('files.0');
    }

    private function image(Vehicle $vehicle, string $name, bool $cover = false): VehicleImage
    {
        $path = "vehicles/{$vehicle->id}/{$name}";
        Storage::disk('public')->put($path, file_get_contents(database_path('seeders/assets/vehicle-placeholder.png')));

        return VehicleImage::create(['vehicle_id' => $vehicle->id, 'path' => $path, 'is_cover' => $cover]);
    }
}
