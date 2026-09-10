<?php

namespace App\Modules\Vehicles\Infrastructure\Persistence\Eloquent;

use App\Modules\Vehicles\Application\Exception\DuplicateVehicleValue;
use App\Modules\Vehicles\Application\Port\VehicleWriteGateway;
use App\Modules\Vehicles\Domain\Model\Vehicle;
use App\Modules\Vehicles\Domain\ValueObject\VehicleId;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final readonly class EloquentVehicleWriteGateway implements VehicleWriteGateway
{
    private const PLATE_CONSTRAINT = 'vehicles_placa_unique';

    private const CHASSIS_CONSTRAINT = 'vehicles_chassi_unique';

    public function __construct(private VehicleMapper $mapper) {}

    public function nextIdentity(): VehicleId
    {
        return new VehicleId((int) DB::selectOne("SELECT nextval(pg_get_serial_sequence('vehicles', 'id')) AS id")->id);
    }

    public function getForUpdate(VehicleId $id): ?Vehicle
    {
        $record = VehicleRecord::query()->with('images')->whereKey($id->value)->lockForUpdate()->first();

        return $record instanceof VehicleRecord ? $this->mapper->toDomain($record) : null;
    }

    public function insert(Vehicle $vehicle, int $createdBy): void
    {
        $record = new VehicleRecord;
        $record->forceFill([
            'id' => $vehicle->id->value,
            'user_id' => $vehicle->ownerId,
            'created_by' => $createdBy,
        ]);
        $this->mapper->apply($vehicle, $record);
        $this->save($record);
    }

    public function update(Vehicle $vehicle): void
    {
        $record = VehicleRecord::query()->findOrFail($vehicle->id->value);
        $this->mapper->apply($vehicle, $record);
        $this->save($record);
        $this->syncImages($vehicle, $record);
    }

    public function delete(Vehicle $vehicle): void
    {
        VehicleRecord::query()->whereKey($vehicle->id->value)->delete();
    }

    private function save(VehicleRecord $record): void
    {
        try {
            DB::transaction(static fn (): bool => $record->save());
        } catch (UniqueConstraintViolationException $exception) {
            $message = $exception->getMessage();

            if (\str_contains($message, self::PLATE_CONSTRAINT)) {
                throw new DuplicateVehicleValue('placa');
            }

            if (\str_contains($message, self::CHASSIS_CONSTRAINT)) {
                throw new DuplicateVehicleValue('chassi');
            }

            throw $exception;
        }
    }

    private function syncImages(Vehicle $vehicle, VehicleRecord $record): void
    {
        $persistedIds = \array_values(\array_filter(\array_map(
            static fn ($image): ?int => $image->id?->value,
            $vehicle->images(),
        )));

        $record->images()->whereNotIn('id', $persistedIds)->delete();
        $record->images()->update(['is_cover' => false]);

        foreach ($vehicle->images() as $image) {
            if ($image->id === null) {
                $created = new VehicleImageRecord(['path' => $image->path]);
                $created->forceFill(['is_cover' => $image->isCover]);
                $record->images()->save($created);

                continue;
            }

            $record->images()->whereKey($image->id->value)->update(['is_cover' => $image->isCover]);
        }
    }
}
