<?php

namespace Tests\Unit\Modules\Vehicles\Domain;

use App\Modules\Vehicles\Domain\Enum\FuelType;
use App\Modules\Vehicles\Domain\Enum\Transmission;
use App\Modules\Vehicles\Domain\Model\Actor;
use App\Modules\Vehicles\Domain\Model\Vehicle;
use App\Modules\Vehicles\Domain\Model\VehicleDetails;
use App\Modules\Vehicles\Domain\Model\VehicleError;
use App\Modules\Vehicles\Domain\Model\VehicleImage;
use App\Modules\Vehicles\Domain\ValueObject\Chassis;
use App\Modules\Vehicles\Domain\ValueObject\Mileage;
use App\Modules\Vehicles\Domain\ValueObject\Money;
use App\Modules\Vehicles\Domain\ValueObject\Plate;
use App\Modules\Vehicles\Domain\ValueObject\VehicleId;
use App\Modules\Vehicles\Domain\ValueObject\VehicleImageId;
use App\Modules\Vehicles\Domain\ValueObject\VehicleVersion;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class VehicleTest extends TestCase
{
    public function test_owner_can_update_details_and_advance_version_once(): void
    {
        $vehicle = $this->vehicle();
        $updatedAt = new DateTimeImmutable('2026-09-10T12:00:00+00:00');

        $outcome = $vehicle->updateDetails(
            $this->details(brand: 'Ford'),
            new Actor(10, false),
            new VehicleVersion(1),
            $updatedAt,
        );

        self::assertFalse($outcome->failed());
        self::assertSame('Ford', $vehicle->details()->brand);
        self::assertSame(2, $vehicle->version()->value);
        self::assertSame(10, $vehicle->updatedBy());
        self::assertSame($updatedAt, $vehicle->updatedAt());
    }

    public function test_stale_version_and_unauthorized_actor_do_not_mutate_vehicle(): void
    {
        $vehicle = $this->vehicle();

        $forbidden = $vehicle->updateDetails($this->details(), new Actor(20, false), new VehicleVersion(1), new DateTimeImmutable);
        $stale = $vehicle->updateDetails($this->details(), new Actor(10, false), new VehicleVersion(2), new DateTimeImmutable);

        self::assertSame(VehicleError::Forbidden, $forbidden->error);
        self::assertSame(VehicleError::VersionConflict, $stale->error);
        self::assertSame(1, $vehicle->version()->value);
    }

    public function test_gallery_assigns_and_promotes_exactly_one_cover(): void
    {
        $vehicle = $this->vehicle();
        $actor = new Actor(10, false);
        $now = new DateTimeImmutable;
        $first = new VehicleImage(new VehicleImageId(1), 'vehicles/1/one.jpg', false, $now);
        $second = new VehicleImage(new VehicleImageId(2), 'vehicles/1/two.jpg', false, $now->modify('+1 second'));

        $vehicle->attachImages([$first, $second], $actor, new VehicleVersion(1), $now);
        $vehicle->selectCover(new VehicleImageId(2), $actor, new VehicleVersion(2), $now);
        $vehicle->removeImage(new VehicleImageId(2), $actor, new VehicleVersion(3), $now);

        self::assertCount(1, $vehicle->images());
        self::assertTrue($vehicle->images()[0]->isCover);
        self::assertSame(4, $vehicle->version()->value);
    }

    public function test_gallery_capacity_failure_does_not_advance_version(): void
    {
        $vehicle = $this->vehicle();
        $now = new DateTimeImmutable;
        $images = [];

        for ($id = 1; $id <= Vehicle::MAXIMUM_IMAGE_COUNT + 1; $id++) {
            $images[] = new VehicleImage(new VehicleImageId($id), "vehicles/1/{$id}.jpg", false, $now);
        }

        $outcome = $vehicle->attachImages($images, new Actor(10, false), new VehicleVersion(1), $now);

        self::assertSame(VehicleError::GalleryCapacityExceeded, $outcome->error);
        self::assertSame(1, $vehicle->version()->value);
        self::assertSame([], $vehicle->images());
    }

    public function test_retirement_authorizes_and_advances_the_version_once(): void
    {
        $vehicle = $this->vehicle();
        $retiredAt = new DateTimeImmutable('2026-09-10T13:00:00+00:00');

        $outcome = $vehicle->retire(new Actor(99, true), new VehicleVersion(1), $retiredAt);

        self::assertFalse($outcome->failed());
        self::assertSame(2, $vehicle->version()->value);
        self::assertSame(99, $vehicle->updatedBy());
        self::assertSame($retiredAt, $vehicle->updatedAt());
    }

    private function vehicle(): Vehicle
    {
        return new Vehicle(
            id: new VehicleId(1),
            ownerId: 10,
            details: $this->details(),
            version: new VehicleVersion(1),
            updatedBy: 10,
            updatedAt: new DateTimeImmutable('2026-09-10T10:00:00+00:00'),
        );
    }

    private function details(string $brand = 'Honda'): VehicleDetails
    {
        return new VehicleDetails(
            plate: new Plate('ABC1D23'),
            chassis: new Chassis('9BWZZZ377VT004251'),
            brand: $brand,
            model: 'Civic',
            trim: 'Touring',
            salePrice: Money::fromDecimal('125900.00'),
            color: 'Cinza',
            mileage: new Mileage(25000),
            transmission: Transmission::Automatic,
            fuelType: FuelType::Flex,
        );
    }
}
