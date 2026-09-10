<?php

namespace Tests\Integration\Modules\Vehicles\Persistence;

use App\Models\User;
use App\Modules\Vehicles\Application\Exception\DuplicateVehicleValue;
use App\Modules\Vehicles\Application\Port\VehicleWriteGateway;
use App\Modules\Vehicles\Domain\Enum\FuelType;
use App\Modules\Vehicles\Domain\Enum\Transmission;
use App\Modules\Vehicles\Domain\Model\Actor;
use App\Modules\Vehicles\Domain\Model\VehicleDetails;
use App\Modules\Vehicles\Domain\ValueObject\Chassis;
use App\Modules\Vehicles\Domain\ValueObject\Mileage;
use App\Modules\Vehicles\Domain\ValueObject\Money;
use App\Modules\Vehicles\Domain\ValueObject\Plate;
use App\Modules\Vehicles\Domain\ValueObject\VehicleId;
use App\Modules\Vehicles\Domain\ValueObject\VehicleVersion;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\EloquentUploadReplayStore;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\VehicleMapper;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\VehicleRecord;
use DateTimeImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class EloquentVehiclePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mapper_round_trips_decimal_money_without_float_conversion(): void
    {
        $record = VehicleRecord::factory()->create(['valor_venda' => '9999999999999.99']);
        $record->load('images');
        $mapper = new VehicleMapper;
        $vehicle = $mapper->toDomain($record);

        $vehicle->updateDetails($this->details('9999999999999.98'), new Actor($record->user_id, false), new VehicleVersion(1), new DateTimeImmutable);
        $mapper->apply($vehicle, $record);

        self::assertSame('9999999999999.98', $vehicle->details()->salePrice->decimal());
        self::assertSame('9999999999999.98', $record->getAttributes()['valor_venda']);
    }

    public function test_write_gateway_uses_for_update_when_loading_an_aggregate(): void
    {
        $record = VehicleRecord::factory()->create();
        $queries = [];
        DB::listen(static function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        DB::transaction(fn () => app(VehicleWriteGateway::class)->getForUpdate(new VehicleId((int) $record->getKey())));

        self::assertNotEmpty(\array_filter($queries, static fn (string $sql): bool => \str_contains(\strtolower($sql), 'for update')));
    }

    public function test_named_unique_constraints_are_translated_by_field(): void
    {
        $existing = VehicleRecord::factory()->create();
        $target = VehicleRecord::factory()->create();
        $target->load('images');
        $vehicle = (new VehicleMapper)->toDomain($target);
        $vehicle->updateDetails(
            new VehicleDetails(new Plate($existing->placa), new Chassis($target->chassi), 'Honda', 'Civic', 'LX', Money::fromDecimal('100.00'), 'Prata', new Mileage(0), Transmission::Manual, FuelType::Flex),
            new Actor($target->user_id, false),
            new VehicleVersion($target->lock_version),
            new DateTimeImmutable,
        );

        try {
            app(VehicleWriteGateway::class)->update($vehicle);
            self::fail('The duplicate plate should fail.');
        } catch (DuplicateVehicleValue $exception) {
            self::assertSame('placa', $exception->field);
        }
    }

    public function test_replay_completion_is_read_back_as_the_original_response(): void
    {
        $user = User::factory()->create();
        $vehicle = VehicleRecord::factory()->forOwner($user)->create();
        $store = app(EloquentUploadReplayStore::class);
        $expiresAt = new DateTimeImmutable('+1 day');
        $key = '550e8400-e29b-41d4-a716-446655440000';
        $fingerprint = \hash('sha256', 'request');
        $claim = $store->claim($user->id, $vehicle->id, $key, $fingerprint, $expiresAt);
        $store->complete($claim->id, ['data' => [['id' => 5]]], 201, '"vehicle-1-v2"');

        $replay = $store->claim($user->id, $vehicle->id, $key, $fingerprint, $expiresAt);

        self::assertSame('completed', $replay->status);
        self::assertSame(201, $replay->responseStatus);
        self::assertSame(['data' => [['id' => 5]]], $replay->responseBody);
    }

    private function details(string $price): VehicleDetails
    {
        return new VehicleDetails(new Plate('ABC1D23'), new Chassis('9BWZZZ377VT004251'), 'Honda', 'Civic', 'LX', Money::fromDecimal($price), 'Prata', new Mileage(0), Transmission::Manual, FuelType::Flex);
    }
}
