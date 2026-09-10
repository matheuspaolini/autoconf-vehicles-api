<?php

namespace Tests\Support\Modules\Vehicles;

use App\Modules\Vehicles\Application\Data\CleanupRequest;
use App\Modules\Vehicles\Application\Data\PageData;
use App\Modules\Vehicles\Application\Data\Pagination;
use App\Modules\Vehicles\Application\Data\RenderedUploadResponse;
use App\Modules\Vehicles\Application\Data\ReplayClaim;
use App\Modules\Vehicles\Application\Data\StoredImage;
use App\Modules\Vehicles\Application\Data\UserSummaryData;
use App\Modules\Vehicles\Application\Data\VehicleCatalogCriteria;
use App\Modules\Vehicles\Application\Data\VehicleData;
use App\Modules\Vehicles\Application\Data\VehicleDetailsData;
use App\Modules\Vehicles\Application\Data\VehicleImageData;
use App\Modules\Vehicles\Application\Data\VehicleImagePageData;
use App\Modules\Vehicles\Application\Exception\DuplicateVehicleValue;
use App\Modules\Vehicles\Application\Port\CleanupOutbox;
use App\Modules\Vehicles\Application\Port\CleanupStore;
use App\Modules\Vehicles\Application\Port\Clock;
use App\Modules\Vehicles\Application\Port\MediaStore;
use App\Modules\Vehicles\Application\Port\TransactionRunner;
use App\Modules\Vehicles\Application\Port\UploadedImagesResponse;
use App\Modules\Vehicles\Application\Port\UploadReplayStore;
use App\Modules\Vehicles\Application\Port\UploadSource;
use App\Modules\Vehicles\Application\Port\VehicleReadGateway;
use App\Modules\Vehicles\Application\Port\VehicleWriteGateway;
use App\Modules\Vehicles\Domain\Model\Vehicle;
use App\Modules\Vehicles\Domain\ValueObject\VehicleId;
use Closure;
use DateTimeImmutable;

final readonly class VehicleTestContext
{
    public InMemoryVehicleGateway $vehicles;

    public ImmediateTransactionRunner $transactions;

    public FixedClock $clock;

    public InMemoryMediaStore $media;

    public InMemoryUploadReplayStore $replays;

    public InMemoryCleanupOutbox $outbox;

    public InMemoryCleanupStore $cleanups;

    public DeterministicUploadedImagesResponse $uploadResponses;

    public function __construct(?DateTimeImmutable $now = null)
    {
        $this->vehicles = new InMemoryVehicleGateway;
        $this->transactions = new ImmediateTransactionRunner;
        $this->clock = new FixedClock($now ?? new DateTimeImmutable('2026-09-10T12:00:00+00:00'));
        $this->media = new InMemoryMediaStore;
        $this->replays = new InMemoryUploadReplayStore;
        $this->outbox = new InMemoryCleanupOutbox;
        $this->cleanups = new InMemoryCleanupStore;
        $this->uploadResponses = new DeterministicUploadedImagesResponse;
    }
}

final class InMemoryVehicleGateway implements VehicleReadGateway, VehicleWriteGateway
{
    /** @var array<int, Vehicle> */
    public array $vehicles = [];

    public int $nextId = 1;

    public ?string $duplicateField = null;

    public function nextIdentity(): VehicleId
    {
        return new VehicleId($this->nextId++);
    }

    public function getForUpdate(VehicleId $id): ?Vehicle
    {
        return $this->vehicles[$id->value] ?? null;
    }

    public function insert(Vehicle $vehicle, int $createdBy): void
    {
        $this->throwDuplicateWhenConfigured();
        $this->vehicles[$vehicle->id->value] = $vehicle;
    }

    public function update(Vehicle $vehicle): void
    {
        $this->throwDuplicateWhenConfigured();
        $this->vehicles[$vehicle->id->value] = $vehicle;
    }

    public function delete(Vehicle $vehicle): void
    {
        unset($this->vehicles[$vehicle->id->value]);
    }

    public function paginate(VehicleCatalogCriteria $criteria): PageData
    {
        $items = \array_map($this->project(...), \array_values($this->vehicles));

        return new PageData($items, 1, 1, $criteria->perPage, \count($items), null, null, null, null, $items === [] ? null : 1, \count($items) ?: null, '/api/vehicles', []);
    }

    public function detail(VehicleId $id): ?VehicleData
    {
        $vehicle = $this->vehicles[$id->value] ?? null;

        return $vehicle instanceof Vehicle ? $this->project($vehicle) : null;
    }

    public function gallery(VehicleId $id, Pagination $pagination): ?VehicleImagePageData
    {
        $detail = $this->detail($id);

        if ($detail === null) {
            return null;
        }

        $page = new PageData($detail->images, 1, 1, $pagination->perPage, \count($detail->images), null, null, null, null, $detail->images === [] ? null : 1, \count($detail->images) ?: null, "/api/vehicles/{$id->value}/images", []);

        return new VehicleImagePageData($page, $detail->version);
    }

    private function project(Vehicle $vehicle): VehicleData
    {
        $details = $vehicle->details();
        $user = new UserSummaryData($vehicle->ownerId, 'Test User');
        $images = [];

        foreach ($vehicle->images() as $index => $image) {
            $images[] = new VehicleImageData($image->id?->value ?? $index + 1, $image->path, $image->isCover, $image->createdAt->format(DATE_ATOM));
        }

        return new VehicleData(
            $vehicle->id->value,
            new VehicleDetailsData($details->plate->value, $details->chassis->value, $details->brand, $details->model, $details->trim, $details->salePrice->decimal(), $details->color, $details->mileage->kilometres, $details->transmission->value, $details->fuelType->value),
            $vehicle->version()->value,
            $user,
            $images[0] ?? null,
            $images,
            $user,
            new UserSummaryData($vehicle->updatedBy(), 'Test User'),
            $vehicle->updatedAt()->format(DATE_ATOM),
            $vehicle->updatedAt()->format(DATE_ATOM),
        );
    }

    private function throwDuplicateWhenConfigured(): void
    {
        if ($this->duplicateField !== null) {
            throw new DuplicateVehicleValue($this->duplicateField);
        }
    }
}

final class ImmediateTransactionRunner implements TransactionRunner
{
    public int $runs = 0;

    public function run(Closure $operation): mixed
    {
        $this->runs++;

        return $operation();
    }
}

final readonly class FixedClock implements Clock
{
    public function __construct(private DateTimeImmutable $time) {}

    public function now(): DateTimeImmutable
    {
        return $this->time;
    }
}

final class InMemoryMediaStore implements MediaStore
{
    /** @var list<string> */
    public array $stored = [];

    /** @var list<string> */
    public array $deleted = [];

    /** @var list<string> */
    public array $deletedDirectories = [];

    public function store(VehicleId $vehicleId, UploadSource $source): StoredImage
    {
        $path = "vehicles/{$vehicleId->value}/".$source->originalName();
        $this->stored[] = $path;

        return new StoredImage($path);
    }

    public function delete(array $paths): void
    {
        $this->deleted = [...$this->deleted, ...$paths];
    }

    public function deleteDirectory(string $directory): void
    {
        $this->deletedDirectories[] = $directory;
    }
}

final class InMemoryUploadReplayStore implements UploadReplayStore
{
    public ReplayClaim $nextClaim;

    /** @var list<int> */
    public array $abandoned = [];

    /** @var array<int, array{body: array<string, mixed>, status: int, etag: string}> */
    public array $completed = [];

    public function __construct()
    {
        $this->nextClaim = new ReplayClaim(1, 'claimed');
    }

    public function claim(int $userId, int $vehicleId, string $key, string $fingerprint, DateTimeImmutable $expiresAt): ReplayClaim
    {
        return $this->nextClaim;
    }

    public function complete(int $claimId, array $body, int $status, string $etag): void
    {
        $this->completed[$claimId] = \compact('body', 'status', 'etag');
    }

    public function abandon(int $claimId): void
    {
        $this->abandoned[] = $claimId;
    }
}

final class InMemoryCleanupOutbox implements CleanupOutbox
{
    /** @var list<CleanupRequest> */
    public array $requests = [];

    public function record(CleanupRequest $request): int
    {
        $this->requests[] = $request;

        return \count($this->requests);
    }
}

final class InMemoryCleanupStore implements CleanupStore
{
    /** @var list<int> */
    public array $overdue = [];

    /** @var array<int, array{paths: list<string>, directory: string|null}> */
    public array $pending = [];

    /** @var list<int> */
    public array $dispatched = [];

    /** @var list<int> */
    public array $completed = [];

    /** @var array<int, string> */
    public array $failed = [];

    public ?DateTimeImmutable $orphanCutoff = null;

    public ?DateTimeImmutable $prunedAt = null;

    public function overdueTaskIds(DateTimeImmutable $cutoff, int $limit): array
    {
        return \array_slice($this->overdue, 0, $limit);
    }

    public function dispatch(int $taskId): void
    {
        $this->dispatched[] = $taskId;
    }

    public function pendingTask(int $taskId): ?array
    {
        return $this->pending[$taskId] ?? null;
    }

    public function complete(int $taskId): void
    {
        $this->completed[] = $taskId;
    }

    public function fail(int $taskId, string $message): void
    {
        $this->failed[$taskId] = $message;
    }

    public function pruneExpiredUploadRequests(DateTimeImmutable $now): void
    {
        $this->prunedAt = $now;
    }

    public function removeOrphans(DateTimeImmutable $cutoff): void
    {
        $this->orphanCutoff = $cutoff;
    }
}

final class DeterministicUploadedImagesResponse implements UploadedImagesResponse
{
    public function render(int $vehicleId, int $vehicleVersion, array $images): RenderedUploadResponse
    {
        return new RenderedUploadResponse(
            ['data' => \array_map(static fn (VehicleImageData $image): array => ['id' => $image->id], $images)],
            201,
            "\"vehicle-{$vehicleId}-v{$vehicleVersion}\"",
        );
    }
}

final class InMemoryUploadSource implements UploadSource
{
    public function __construct(private readonly string $name, private readonly string $contents = 'image') {}

    public function originalName(): string
    {
        return $this->name;
    }

    public function mediaType(): string
    {
        return 'image/png';
    }

    public function sizeInBytes(): int
    {
        return \strlen($this->contents);
    }

    public function contentHash(): string
    {
        return \hash('sha256', $this->contents);
    }

    public function openStream()
    {
        $stream = \fopen('php://memory', 'r+');
        \fwrite($stream, $this->contents);
        \rewind($stream);

        return $stream;
    }
}
