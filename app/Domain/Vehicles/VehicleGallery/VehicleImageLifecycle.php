<?php

namespace App\Domain\Vehicles\VehicleGallery;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Throwable;

final class VehicleImageLifecycle
{
    public function __construct(
        private readonly Filesystem $disk,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return Collection<int, VehicleImage>
     */
    public function upload(Vehicle $vehicle, User $actor, array $files): Collection
    {
        $paths = [];

        try {
            foreach ($files as $file) {
                $path = $this->disk->putFile("vehicles/{$vehicle->getKey()}", $file);

                if (! is_string($path)) {
                    throw new \RuntimeException('Vehicle image could not be stored.');
                }

                $paths[] = $path;
            }

            return DB::transaction(function () use ($vehicle, $actor, $paths): Collection {
                /** @var Vehicle $lockedVehicle */
                $lockedVehicle = Vehicle::query()
                    ->whereKey($vehicle->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $galleryWasEmpty = ! $lockedVehicle->images()->exists();
                $created = new Collection;

                foreach ($paths as $index => $path) {
                    $image = new VehicleImage(['path' => $path]);
                    $image->forceFill([
                        'is_cover' => $galleryWasEmpty && $index === 0,
                    ]);
                    $lockedVehicle->images()->save($image);
                    $created->push($image);
                }

                $lockedVehicle->forceFill(['updated_by' => $actor->getKey()])->touch();

                return $created;
            });
        } catch (Throwable $exception) {
            $this->disk->delete($paths);

            throw $exception;
        }
    }

    public function setCover(Vehicle $vehicle, VehicleImage $image, User $actor): VehicleImage
    {
        return DB::transaction(function () use ($vehicle, $image, $actor): VehicleImage {
            /** @var Vehicle $lockedVehicle */
            $lockedVehicle = Vehicle::query()
                ->whereKey($vehicle->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            /** @var VehicleImage $targetImage */
            $targetImage = $lockedVehicle->images()
                ->whereKey($image->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedVehicle->images()->update(['is_cover' => false]);
            $targetImage->forceFill(['is_cover' => true])->save();
            $lockedVehicle->forceFill(['updated_by' => $actor->getKey()])->touch();

            return $targetImage->refresh();
        });
    }

    public function delete(Vehicle $vehicle, VehicleImage $image, User $actor): void
    {
        [$path, $imageId] = DB::transaction(function () use ($vehicle, $image, $actor): array {
            /** @var Vehicle $lockedVehicle */
            $lockedVehicle = Vehicle::query()
                ->whereKey($vehicle->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            /** @var Collection<int, VehicleImage> $images */
            $images = $lockedVehicle->images()->lockForUpdate()->orderBy('id')->get();
            $target = $images->firstWhere('id', $image->getKey());

            if (! $target instanceof VehicleImage) {
                throw (new ModelNotFoundException)->setModel(VehicleImage::class, [$image->getKey()]);
            }

            $path = $target->path;
            $imageId = $target->getKey();
            $wasCover = $target->is_cover;
            $target->delete();

            if ($wasCover && ($replacement = $images->firstWhere('id', '!=', $imageId)) instanceof VehicleImage) {
                $replacement->forceFill(['is_cover' => true])->save();
            }

            $lockedVehicle->forceFill(['updated_by' => $actor->getKey()])->touch();

            return [$path, $imageId];
        });

        if (! $this->disk->delete($path)) {
            $this->logger->warning(
                'Vehicle image file could not be deleted',
                [
                    'vehicle_id' => $vehicle->getKey(),
                    'image_id' => $imageId,
                    'path' => $path,
                ],
            );
        }
    }

    public function prepareVehicleDeletion(Vehicle $lockedVehicle): PendingVehicleGalleryCleanup
    {
        /** @var list<string> $paths */
        $paths = $lockedVehicle->images()->orderBy('id')->pluck('path')->all();

        return new PendingVehicleGalleryCleanup(
            $lockedVehicle->getKey(),
            $paths,
            $this->disk,
            $this->logger,
        );
    }
}
