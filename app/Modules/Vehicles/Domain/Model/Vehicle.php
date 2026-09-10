<?php

namespace App\Modules\Vehicles\Domain\Model;

use App\Modules\Vehicles\Domain\ValueObject\VehicleId;
use App\Modules\Vehicles\Domain\ValueObject\VehicleImageId;
use App\Modules\Vehicles\Domain\ValueObject\VehicleVersion;
use DateTimeImmutable;

final class Vehicle
{
    public const MAXIMUM_IMAGE_COUNT = 20;

    /** @param list<VehicleImage> $images */
    public function __construct(
        public readonly VehicleId $id,
        public readonly int $ownerId,
        private VehicleDetails $details,
        private VehicleVersion $version,
        private int $updatedBy,
        private DateTimeImmutable $updatedAt,
        private array $images = [],
    ) {}

    public function details(): VehicleDetails
    {
        return $this->details;
    }

    public function version(): VehicleVersion
    {
        return $this->version;
    }

    public function updatedBy(): int
    {
        return $this->updatedBy;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return list<VehicleImage> */
    public function images(): array
    {
        return $this->images;
    }

    public function updateDetails(VehicleDetails $details, Actor $actor, VehicleVersion $expectedVersion, DateTimeImmutable $occurredAt): VehicleMutationOutcome
    {
        if (($failure = $this->requireMutation($actor, $expectedVersion)) instanceof VehicleMutationOutcome) {
            return $failure;
        }

        $this->details = $details;
        $this->advanceVersion($actor, $occurredAt);

        return VehicleMutationOutcome::success();
    }

    /** @param list<VehicleImage> $images */
    public function attachImages(array $images, Actor $actor, VehicleVersion $expectedVersion, DateTimeImmutable $occurredAt): VehicleMutationOutcome
    {
        if (($failure = $this->requireMutation($actor, $expectedVersion)) instanceof VehicleMutationOutcome) {
            return $failure;
        }

        if (\count($this->images) + \count($images) > self::MAXIMUM_IMAGE_COUNT) {
            return VehicleMutationOutcome::failure(VehicleError::GalleryCapacityExceeded);
        }

        $galleryWasEmpty = $this->images === [];

        foreach ($images as $index => $image) {
            $image->isCover = $galleryWasEmpty && $index === 0;
            $this->images[] = $image;
        }

        $this->advanceVersion($actor, $occurredAt);

        return VehicleMutationOutcome::success();
    }

    public function selectCover(VehicleImageId $imageId, Actor $actor, VehicleVersion $expectedVersion, DateTimeImmutable $occurredAt): VehicleMutationOutcome
    {
        if (($failure = $this->requireMutation($actor, $expectedVersion)) instanceof VehicleMutationOutcome) {
            return $failure;
        }

        $target = $this->findImage($imageId);

        if (! $target instanceof VehicleImage) {
            return VehicleMutationOutcome::failure(VehicleError::ImageNotFound);
        }

        foreach ($this->images as $image) {
            $image->isCover = $image === $target;
        }

        $this->advanceVersion($actor, $occurredAt);

        return VehicleMutationOutcome::success();
    }

    public function removeImage(VehicleImageId $imageId, Actor $actor, VehicleVersion $expectedVersion, DateTimeImmutable $occurredAt): VehicleMutationOutcome
    {
        if (($failure = $this->requireMutation($actor, $expectedVersion)) instanceof VehicleMutationOutcome) {
            return $failure;
        }

        foreach ($this->images as $index => $image) {
            if ($image->id?->value !== $imageId->value) {
                continue;
            }

            $wasCover = $image->isCover;
            unset($this->images[$index]);
            $this->images = \array_values($this->images);

            if ($wasCover) {
                $this->promoteOldestImageToCover();
            }

            $this->advanceVersion($actor, $occurredAt);

            return VehicleMutationOutcome::success();
        }

        return VehicleMutationOutcome::failure(VehicleError::ImageNotFound);
    }

    public function retire(Actor $actor, VehicleVersion $expectedVersion, DateTimeImmutable $occurredAt): VehicleMutationOutcome
    {
        if (($failure = $this->requireMutation($actor, $expectedVersion)) instanceof VehicleMutationOutcome) {
            return $failure;
        }

        $this->advanceVersion($actor, $occurredAt);

        return VehicleMutationOutcome::success();
    }

    private function requireMutation(Actor $actor, VehicleVersion $expectedVersion): ?VehicleMutationOutcome
    {
        if (! $actor->isAdministrator && $actor->userId !== $this->ownerId) {
            return VehicleMutationOutcome::failure(VehicleError::Forbidden);
        }

        if ($expectedVersion->value !== $this->version->value) {
            return VehicleMutationOutcome::failure(VehicleError::VersionConflict);
        }

        return null;
    }

    private function advanceVersion(Actor $actor, DateTimeImmutable $occurredAt): void
    {
        $this->version = $this->version->next();
        $this->updatedBy = $actor->userId;
        $this->updatedAt = $occurredAt;
    }

    private function findImage(VehicleImageId $imageId): ?VehicleImage
    {
        foreach ($this->images as $image) {
            if ($image->id?->value === $imageId->value) {
                return $image;
            }
        }

        return null;
    }

    private function promoteOldestImageToCover(): void
    {
        if ($this->images !== []) {
            $this->images[0]->isCover = true;
        }
    }
}
