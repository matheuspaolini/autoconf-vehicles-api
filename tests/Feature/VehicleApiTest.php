<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_non_admin_authenticated_user(): void
    {
        $response = $this->postJson('/api/auth/register', ['name' => 'Ana', 'email' => 'ANA@EXAMPLE.COM', 'password' => 'password1', 'password_confirmation' => 'password1']);

        $response->assertCreated()->assertJsonPath('data.email', 'ana@example.com')->assertJsonPath('data.is_admin', false);
        $this->assertAuthenticated();
    }

    public function test_login_me_and_logout_use_the_session_guard(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com', 'password' => 'password1']);

        $this->postJson('/api/auth/login', ['email' => 'USER@EXAMPLE.COM', 'password' => 'password1'])
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
        $this->assertAuthenticatedAs($user);
        $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('data.email', 'user@example.com');
        $this->postJson('/api/auth/logout')->assertNoContent();
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_invalid_logins_are_validation_errors_and_are_rate_limited(): void
    {
        User::factory()->create(['email' => 'user@example.com', 'password' => 'password1']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/auth/login', ['email' => 'user@example.com', 'password' => 'wrong-password'])->assertUnprocessable()->assertJsonValidationErrors('email');
        }

        $this->postJson('/api/auth/login', ['email' => 'user@example.com', 'password' => 'wrong-password'])->assertTooManyRequests();
    }

    public function test_every_response_echoes_or_generates_a_request_identifier(): void
    {
        $this->getJson('/up', ['X-Request-Id' => 'catalog-request-123'])->assertOk()->assertHeader('X-Request-Id', 'catalog-request-123');
        $this->getJson('/up')->assertOk()->assertHeader('X-Request-Id');
    }

    public function test_an_owner_can_create_a_normalized_vehicle_but_a_guest_cannot_list(): void
    {
        $this->getJson('/api/vehicles')->assertUnauthorized();
        $actor = User::factory()->create();
        $this->actingAs($actor)->postJson('/api/vehicles', $this->payload())->assertCreated()->assertJsonPath('data.placa', 'ABC1D23');
        $this->assertDatabaseHas('vehicles', ['user_id' => $actor->id, 'created_by' => $actor->id, 'updated_by' => $actor->id, 'placa' => 'ABC1D23']);
    }

    public function test_a_guest_receives_json_unauthorized_without_an_accept_header(): void
    {
        $this->get('/api/vehicles')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_vehicle_validation_and_mass_assignment_protect_owner_and_audit_fields(): void
    {
        $actor = User::factory()->create();
        $other = User::factory()->create();
        $payload = $this->payload() + ['user_id' => $other->id, 'created_by' => $other->id, 'updated_by' => $other->id, 'is_admin' => true];

        $this->actingAs($actor)->postJson('/api/vehicles', $payload)->assertCreated();
        $this->assertDatabaseHas('vehicles', ['placa' => 'ABC1D23', 'user_id' => $actor->id, 'created_by' => $actor->id, 'updated_by' => $actor->id]);
        $this->assertFalse($actor->refresh()->is_admin);
        $this->actingAs($actor)->postJson('/api/vehicles', array_replace($this->payload(), ['placa' => 'bad', 'chassi' => 'invalid', 'valor_venda' => '0', 'km' => -1, 'cambio' => 'cvt']))->assertUnprocessable()->assertJsonValidationErrors(['placa', 'chassi', 'valor_venda', 'km', 'cambio']);
    }

    public function test_only_owner_or_admin_can_mutate_a_vehicle(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();
        $this->actingAs($stranger)->withHeader('If-Match', $this->etag($vehicle))->patchJson("/api/vehicles/{$vehicle->id}", $this->payload())->assertForbidden();
        $this->actingAs($admin)->withHeader('If-Match', $this->etag($vehicle))->patchJson("/api/vehicles/{$vehicle->id}", $this->payload())->assertOk();
    }

    public function test_first_uploaded_image_becomes_cover_and_its_file_is_stored(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();
        $this->actingAs($owner)
            ->withHeaders(['If-Match' => $this->etag($vehicle), 'Idempotency-Key' => (string) str()->uuid()])
            ->post("/api/vehicles/{$vehicle->id}/images", ['files' => [UploadedFile::fake()->createWithContent('car.png', file_get_contents(database_path('seeders/assets/vehicle-placeholder.png')))]])
            ->assertCreated();
        $image = $vehicle->images()->firstOrFail();
        $this->assertTrue($image->is_cover);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_public_vehicle_images_are_served_without_a_signature(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('vehicles/10/placeholder.png', 'image-content');

        $this->get('/storage/vehicles/10/placeholder.png')
            ->assertOk();
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return ['placa' => 'abc1d23', 'chassi' => '9BWZZZ377VT004251', 'marca' => 'Chevrolet', 'modelo' => 'Onix', 'versao' => 'LT', 'valor_venda' => '78900.00', 'cor' => 'Prata', 'km' => 100, 'cambio' => 'automatico', 'combustivel' => 'flex'];
    }

    private function etag(Vehicle $vehicle): string
    {
        return "\"vehicle-{$vehicle->id}-v{$vehicle->lock_version}\"";
    }
}
