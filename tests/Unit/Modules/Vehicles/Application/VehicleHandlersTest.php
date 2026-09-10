<?php

namespace Tests\Unit\Modules\Vehicles\Application;

use App\Modules\Vehicles\Application\Command\CompleteVehicleMediaCleanupHandler;
use App\Modules\Vehicles\Application\Command\CreateVehicle;
use App\Modules\Vehicles\Application\Command\CreateVehicleHandler;
use App\Modules\Vehicles\Application\Command\DeleteVehicle;
use App\Modules\Vehicles\Application\Command\DeleteVehicleHandler;
use App\Modules\Vehicles\Application\Command\DeleteVehicleImage;
use App\Modules\Vehicles\Application\Command\DeleteVehicleImageHandler;
use App\Modules\Vehicles\Application\Command\ReconcileVehicleMediaHandler;
use App\Modules\Vehicles\Application\Command\SetVehicleCover;
use App\Modules\Vehicles\Application\Command\SetVehicleCoverHandler;
use App\Modules\Vehicles\Application\Command\UpdateVehicle;
use App\Modules\Vehicles\Application\Command\UpdateVehicleHandler;
use App\Modules\Vehicles\Application\Command\UploadFingerprint;
use App\Modules\Vehicles\Application\Command\UploadVehicleImages;
use App\Modules\Vehicles\Application\Command\UploadVehicleImagesHandler;
use App\Modules\Vehicles\Application\Command\VehicleDetailsFactory;
use App\Modules\Vehicles\Application\Data\Pagination;
use App\Modules\Vehicles\Application\Data\ReplayClaim;
use App\Modules\Vehicles\Application\Data\VehicleCatalogCriteria;
use App\Modules\Vehicles\Application\Data\VehicleDetailsData;
use App\Modules\Vehicles\Application\Query\GetVehicleDetailHandler;
use App\Modules\Vehicles\Application\Query\ListVehicleImagesHandler;
use App\Modules\Vehicles\Application\Query\ListVehiclesHandler;
use App\Modules\Vehicles\Application\Result\CreateVehicleError;
use App\Modules\Vehicles\Application\Result\DeleteVehicleError;
use App\Modules\Vehicles\Application\Result\SetVehicleCoverError;
use App\Modules\Vehicles\Application\Result\UpdateVehicleError;
use App\Modules\Vehicles\Application\Result\UploadVehicleImagesError;
use App\Modules\Vehicles\Domain\Enum\FuelType;
use App\Modules\Vehicles\Domain\Enum\Transmission;
use App\Modules\Vehicles\Domain\Model\Actor;
use App\Modules\Vehicles\Domain\Model\Vehicle;
use App\Modules\Vehicles\Domain\Model\VehicleDetails;
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
use Tests\Support\Modules\Vehicles\InMemoryUploadSource;
use Tests\Support\Modules\Vehicles\VehicleTestContext;

final class VehicleHandlersTest extends TestCase
{
    public function test_create_returns_projection_and_translates_duplicate_constraint(): void
    {
        $context = new VehicleTestContext;
        $handler = new CreateVehicleHandler($context->vehicles, $context->vehicles, $context->transactions, $context->clock, new VehicleDetailsFactory);
        $command = new CreateVehicle(new Actor(10, false), $this->detailsData());

        $created = $handler->handle($command);
        $context->vehicles->duplicateField = 'placa';
        $duplicate = $handler->handle($command);

        self::assertTrue($created->succeeded());
        self::assertSame(10, $created->vehicle?->owner->id);
        self::assertSame(CreateVehicleError::DuplicatePlate, $duplicate->error);
        self::assertSame(['The placa has already been taken.'], $duplicate->fieldErrors['placa']);
    }

    public function test_update_enforces_authorization_version_and_duplicate_translation(): void
    {
        $context = new VehicleTestContext;
        $context->vehicles->vehicles[1] = $this->vehicle();
        $handler = new UpdateVehicleHandler($context->vehicles, $context->vehicles, $context->transactions, $context->clock, new VehicleDetailsFactory);

        $forbidden = $handler->handle(new UpdateVehicle(1, 1, new Actor(20, false), $this->detailsData()));
        $stale = $handler->handle(new UpdateVehicle(1, 2, new Actor(10, false), $this->detailsData()));
        $context->vehicles->duplicateField = 'chassi';
        $duplicate = $handler->handle(new UpdateVehicle(1, 1, new Actor(10, false), $this->detailsData()));

        self::assertSame(UpdateVehicleError::Forbidden, $forbidden->error);
        self::assertSame(UpdateVehicleError::VersionConflict, $stale->error);
        self::assertSame(UpdateVehicleError::DuplicateChassis, $duplicate->error);
        self::assertSame(['The chassi has already been taken.'], $duplicate->fieldErrors['chassi']);
    }

    public function test_delete_records_cleanup_only_after_a_valid_mutation(): void
    {
        $context = new VehicleTestContext;
        $context->vehicles->vehicles[1] = $this->vehicle(withImage: true);
        $handler = new DeleteVehicleHandler($context->vehicles, $context->outbox, $context->transactions, $context->clock);

        $stale = $handler->handle(new DeleteVehicle(1, 2, new Actor(10, false)));
        $deleted = $handler->handle(new DeleteVehicle(1, 1, new Actor(10, false)));

        self::assertSame(DeleteVehicleError::VersionConflict, $stale->error);
        self::assertTrue($deleted->succeeded());
        self::assertArrayNotHasKey(1, $context->vehicles->vehicles);
        self::assertSame(['vehicles/1/one.png'], $context->outbox->requests[0]->paths);
    }

    public function test_gallery_handlers_reject_cross_vehicle_images_and_remove_members(): void
    {
        $context = new VehicleTestContext;
        $context->vehicles->vehicles[1] = $this->vehicle(withImage: true);
        $cover = new SetVehicleCoverHandler($context->vehicles, $context->vehicles, $context->transactions, $context->clock);
        $delete = new DeleteVehicleImageHandler($context->vehicles, $context->outbox, $context->transactions, $context->clock);

        $unknown = $cover->handle(new SetVehicleCover(1, 99, 1, new Actor(10, false)));
        $removed = $delete->handle(new DeleteVehicleImage(1, 1, 1, new Actor(10, false)));

        self::assertSame(SetVehicleCoverError::ImageNotFound, $unknown->error);
        self::assertTrue($removed->succeeded());
        self::assertSame([], $context->vehicles->vehicles[1]->images());
        self::assertSame(['vehicles/1/one.png'], $context->outbox->requests[0]->paths);
    }

    public function test_upload_replays_before_version_checks_and_rolls_back_failed_mutations(): void
    {
        $context = new VehicleTestContext;
        $context->vehicles->vehicles[1] = $this->vehicle();
        $handler = $this->uploadHandler($context);
        $file = new InMemoryUploadSource('one.png');
        $command = new UploadVehicleImages(1, null, true, 'key', new Actor(10, false), [$file]);
        $context->replays->nextClaim = new ReplayClaim(1, 'completed', 201, ['data' => [['id' => 7]]], '"vehicle-1-v2"');

        $replayed = $handler->handle($command);
        $context->replays->nextClaim = new ReplayClaim(2, 'claimed');
        $missingVersion = $handler->handle($command);

        self::assertTrue($replayed->succeeded());
        self::assertSame('"vehicle-1-v2"', $replayed->response?->etag);
        self::assertSame([], $context->media->stored);
        self::assertSame(UploadVehicleImagesError::MissingVersion, $missingVersion->error);
        self::assertSame([2], $context->replays->abandoned);
    }

    public function test_upload_capacity_failure_deletes_newly_stored_media(): void
    {
        $context = new VehicleTestContext;
        $context->vehicles->vehicles[1] = $this->vehicle(imageCount: Vehicle::MAXIMUM_IMAGE_COUNT);
        $context->replays->nextClaim = new ReplayClaim(3, 'claimed');

        $result = $this->uploadHandler($context)->handle(new UploadVehicleImages(
            1,
            1,
            false,
            'key',
            new Actor(10, false),
            [new InMemoryUploadSource('overflow.png')],
        ));

        self::assertSame(UploadVehicleImagesError::GalleryCapacityExceeded, $result->error);
        self::assertSame(['vehicles/1/overflow.png'], $context->media->deleted);
        self::assertSame([3], $context->replays->abandoned);
    }

    public function test_cleanup_handlers_are_deterministic_through_their_ports(): void
    {
        $context = new VehicleTestContext;
        $context->cleanups->overdue = [4, 5];
        $context->cleanups->pending[4] = ['paths' => ['vehicles/1/old.png'], 'directory' => 'vehicles/1'];

        (new ReconcileVehicleMediaHandler($context->cleanups, $context->clock))->handle();
        (new CompleteVehicleMediaCleanupHandler($context->cleanups, $context->media))->handle(4);

        self::assertSame([4, 5], $context->cleanups->dispatched);
        self::assertSame(['vehicles/1/old.png'], $context->media->deleted);
        self::assertSame(['vehicles/1'], $context->media->deletedDirectories);
        self::assertSame([4], $context->cleanups->completed);
        self::assertNotNull($context->cleanups->orphanCutoff);
        self::assertNotNull($context->cleanups->prunedAt);
    }

    public function test_query_handlers_return_gateway_projections(): void
    {
        $context = new VehicleTestContext;
        $context->vehicles->vehicles[1] = $this->vehicle(withImage: true);
        $criteria = new VehicleCatalogCriteria(filters: [], search: null, ownerId: null, sortTerms: []);

        $catalog = (new ListVehiclesHandler($context->vehicles))->handle($criteria);
        $detail = (new GetVehicleDetailHandler($context->vehicles))->handle(1);
        $gallery = (new ListVehicleImagesHandler($context->vehicles))->handle(1, new Pagination(20, null));

        self::assertSame(1, $catalog->total);
        self::assertSame(1, $detail?->id);
        self::assertSame(1, $gallery?->page->total);
        self::assertSame(1, $gallery?->vehicleVersion);
    }

    private function uploadHandler(VehicleTestContext $context): UploadVehicleImagesHandler
    {
        return new UploadVehicleImagesHandler(
            $context->vehicles,
            $context->vehicles,
            $context->replays,
            $context->media,
            $context->uploadResponses,
            $context->transactions,
            $context->clock,
            new UploadFingerprint,
        );
    }

    private function vehicle(bool $withImage = false, int $imageCount = 0): Vehicle
    {
        $now = new DateTimeImmutable('2026-09-10T10:00:00+00:00');
        $images = $withImage ? [new VehicleImage(new VehicleImageId(1), 'vehicles/1/one.png', true, $now)] : [];

        for ($id = 1; $id <= $imageCount; $id++) {
            $images[] = new VehicleImage(new VehicleImageId($id), "vehicles/1/{$id}.png", $id === 1, $now->modify("+{$id} seconds"));
        }

        return new Vehicle(new VehicleId(1), 10, $this->details(), new VehicleVersion(1), 10, $now, $images);
    }

    private function details(): VehicleDetails
    {
        return new VehicleDetails(new Plate('ABC1D23'), new Chassis('9BWZZZ377VT004251'), 'Honda', 'Civic', 'Touring', Money::fromDecimal('125900.00'), 'Cinza', new Mileage(25000), Transmission::Automatic, FuelType::Flex);
    }

    private function detailsData(): VehicleDetailsData
    {
        return new VehicleDetailsData('ABC1D23', '9BWZZZ377VT004251', 'Honda', 'Civic', 'Touring', '125900.00', 'Cinza', 25000, 'automatico', 'flex');
    }
}
