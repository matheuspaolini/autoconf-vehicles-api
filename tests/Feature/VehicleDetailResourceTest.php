<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\VehicleImageRecord as VehicleImage;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\VehicleRecord as Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleDetailResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_vehicle_returns_detail_fields_with_an_empty_embedded_gallery(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->postJson('/api/vehicles', $this->payload())
            ->assertCreated()
            ->assertJsonCount(0, 'data.images')
            ->assertJsonPath('data.owner.id', $owner->id)
            ->assertJsonPath('data.permissions.update', true)
            ->assertJsonPath('data.audit.created_by.id', $owner->id)
            ->assertJsonPath('data.valor_venda', '78900.00');
    }

    public function test_detail_and_gallery_endpoint_return_images_cover_first_without_exposing_paths(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();
        $oldestNonCover = VehicleImage::factory()->for($vehicle)->create();
        $cover = VehicleImage::factory()->for($vehicle)->cover()->create();
        $newestNonCover = VehicleImage::factory()->for($vehicle)->create();

        $this->actingAs($owner)
            ->getJson("/api/vehicles/{$vehicle->id}")
            ->assertOk()
            ->assertHeader('ETag', $this->etag($vehicle))
            ->assertJsonPath('data.images.0.id', $cover->id)
            ->assertJsonPath('data.images.1.id', $oldestNonCover->id)
            ->assertJsonPath('data.images.2.id', $newestNonCover->id)
            ->assertJsonCount(3, 'data.images')
            ->assertJsonMissingPath('data.images.0.path')
            ->assertJsonPath('data.permissions.update', true)
            ->assertJsonPath('data.audit.updated_by.id', $owner->id);

        $this->actingAs($owner)
            ->getJson("/api/vehicles/{$vehicle->id}/images?per_page=20")
            ->assertOk()
            ->assertJsonPath('data.0.id', $cover->id)
            ->assertJsonPath('data.1.id', $oldestNonCover->id)
            ->assertJsonPath('data.2.id', $newestNonCover->id)
            ->assertJsonPath('meta.total', 3);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_replace([
            'placa' => 'ABC1D23',
            'chassi' => '9BWZZZ377VT004251',
            'marca' => 'Chevrolet',
            'modelo' => 'Onix',
            'versao' => 'LT',
            'valor_venda' => '78900.00',
            'cor' => 'Prata',
            'km' => 100,
            'cambio' => 'automatico',
            'combustivel' => 'flex',
        ], $overrides);
    }

    private function etag(Vehicle $vehicle): string
    {
        return "\"vehicle-{$vehicle->id}-v{$vehicle->lock_version}\"";
    }
}
