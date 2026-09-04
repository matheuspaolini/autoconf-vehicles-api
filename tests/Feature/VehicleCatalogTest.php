<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_filters_are_case_insensitive_and_escape_wildcards(): void
    {
        $actor = User::factory()->create();
        Vehicle::factory()->forOwner($actor)->create(['placa' => 'ABC1D23', 'chassi' => '9BWZZZ377VT004251', 'marca' => 'Chevrolet', 'modelo' => 'Onix']);
        Vehicle::factory()->forOwner($actor)->create(['placa' => 'DEF2G34', 'chassi' => '1HGCM82633A004352', 'marca' => 'Ford', 'modelo' => 'Ka']);

        $this->actingAs($actor)->getJson('/api/vehicles?q=ONIX')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.placa', 'ABC1D23');
        $this->actingAs($actor)->getJson('/api/vehicles?marca=%25')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_catalog_combines_filters_and_uses_stable_multi_column_sorting(): void
    {
        $actor = User::factory()->create();
        $first = Vehicle::factory()->forOwner($actor)->create(['placa' => 'AAA1A11', 'chassi' => '9BWZZZ377VT004251', 'marca' => 'Honda', 'modelo' => 'Civic', 'km' => 100, 'valor_venda' => 90000]);
        $second = Vehicle::factory()->forOwner($actor)->create(['placa' => 'BBB2B22', 'chassi' => '1HGCM82633A004352', 'marca' => 'Honda', 'modelo' => 'Civic', 'km' => 100, 'valor_venda' => 120000]);

        $response = $this->actingAs($actor)->getJson('/api/vehicles?marca=honda&modelo=civic&sort=km,-valor_venda&per_page=15');

        $response->assertOk()->assertJsonPath('data.0.id', $second->id)->assertJsonPath('data.1.id', $first->id)->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_mine_scope_returns_only_the_authenticated_users_vehicles_and_keeps_catalog_filters(): void
    {
        $actor = User::factory()->create();
        $other = User::factory()->create();
        $first = Vehicle::factory()->forOwner($actor)->create(['marca' => 'Honda', 'modelo' => 'Civic', 'placa' => 'AAA1A11']);
        Vehicle::factory()->forOwner($actor)->create(['marca' => 'Ford', 'modelo' => 'Ka', 'placa' => 'BBB2B22']);
        Vehicle::factory()->forOwner($other)->create(['marca' => 'Honda', 'modelo' => 'Civic', 'placa' => 'CCC3C33']);

        $this->actingAs($actor)
            ->getJson('/api/vehicles?scope=mine&marca=honda&per_page=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('data.0.id', $first->id);
    }

    public function test_unsupported_sort_and_large_page_size_are_rejected(): void
    {
        $actor = User::factory()->create();

        $this->actingAs($actor)->getJson('/api/vehicles?sort=user_id')->assertUnprocessable()->assertJsonValidationErrors('sort');
        $this->actingAs($actor)->getJson('/api/vehicles?per_page=101')->assertUnprocessable()->assertJsonValidationErrors('per_page');
        $this->actingAs($actor)->getJson('/api/vehicles?scope=other')->assertUnprocessable()->assertJsonValidationErrors('scope');
    }

    public function test_catalog_requests_are_rate_limited_per_authenticated_user(): void
    {
        $actor = User::factory()->create();

        $this->actingAs($actor);

        for ($attempt = 0; $attempt < 60; $attempt++) {
            $this->getJson('/api/vehicles')->assertOk();
        }

        $this->getJson('/api/vehicles')->assertTooManyRequests();
    }

    public function test_vehicle_values_are_serialized_as_contract_types_and_permissions_are_server_calculated(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create(['valor_venda' => '125900.00']);

        $this->actingAs($owner)->getJson("/api/vehicles/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('data.valor_venda', '125900.00')
            ->assertJsonPath('data.permissions.update', true)
            ->assertJsonPath('data.cover_image', null)
            ->assertJsonMissingPath('data.images.0.path');
    }
}
