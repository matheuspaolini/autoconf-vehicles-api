<?php

namespace App\Domain\Vehicles\VehicleGallery;

use App\Domain\Vehicles\VehicleMediaRetirement;
use App\Domain\Vehicles\VehicleVersion;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class VehicleImageLifecycle
{
    public function __construct(
        private readonly Filesystem $disk,
        private readonly VehicleMediaRetirement $mediaRetirement,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return Collection<int, VehicleImage>
     */
    public function upload(Vehicle $vehicle, User $actor, array $files, int $expectedVersion): Collection
    {
        $paths = [];

        try {
            foreach ($files as $file) {
                $path = $this->disk->putFile("vehicles/{$vehicle->getKey()}", $file);

                if (! \is_string($path)) {
                    throw new \RuntimeException('Vehicle image could not be stored.');
                }

                $paths[] = $path;
            }

            return DB::transaction(function () use ($vehicle, $actor, $paths, $expectedVersion): Collection {
                /** @var Vehicle $lockedVehicle */
                $lockedVehicle = Vehicle::query()
                    ->whereKey($vehicle->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                app(VehicleVersion::class)->assertCurrent($lockedVehicle, $expectedVersion);

                if ($lockedVehicle->images()->count() + \count($paths) > 20) {
                    throw ValidationException::withMessages([
                        'files' => ['A Vehicle Gallery can contain at most 20 images.'],
                    ]);
                }

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

                $lockedVehicle->forceFill([
                    'updated_by' => $actor->getKey(),
                    'lock_version' => $lockedVehicle->lock_version + 1,
                ])->touch();

                return $created;
            });
        } catch (Throwable $exception) {
            $this->disk->delete($paths);

            throw $exception;
        }
    }

    public function setCover(Vehicle $vehicle, VehicleImage $image, User $actor, int $expectedVersion): VehicleImage
    {
        return DB::transaction(function () use ($vehicle, $image, $actor, $expectedVersion): VehicleImage {
            /** @var Vehicle $lockedVehicle */
            $lockedVehicle = Vehicle::query()
                ->whereKey($vehicle->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            app(VehicleVersion::class)->assertCurrent($lockedVehicle, $expectedVersion);

            /** @var VehicleImage $targetImage */
            $targetImage = $lockedVehicle->images()
                ->whereKey($image->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedVehicle->images()->update(['is_cover' => false]);
            $targetImage->forceFill(['is_cover' => true])->save();
            $lockedVehicle->forceFill([
                'updated_by' => $actor->getKey(),
                'lock_version' => $lockedVehicle->lock_version + 1,
            ])->touch();

            return $targetImage->refresh();
        });
    }

    public function delete(Vehicle $vehicle, VehicleImage $image, User $actor, int $expectedVersion): void
    {
        DB::transaction(function () use ($vehicle, $image, $actor, $expectedVersion): void {
            /** @var Vehicle $lockedVehicle */
            $lockedVehicle = Vehicle::query()
                ->whereKey($vehicle->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            app(VehicleVersion::class)->assertCurrent($lockedVehicle, $expectedVersion);

            /** @var VehicleImage $target */
            $target = $lockedVehicle->images()->whereKey($image->getKey())->lockForUpdate()->firstOrFail();

            $path = $target->path;
            $imageId = $target->getKey();
            $wasCover = $target->is_cover;
            $target->delete();

            if ($wasCover && ($replacement = $lockedVehicle->images()->whereKeyNot($imageId)->orderBy('id')->lockForUpdate()->first()) instanceof VehicleImage) {
                $replacement->forceFill(['is_cover' => true])->save();
            }

            $lockedVehicle->forceFill([
                'updated_by' => $actor->getKey(),
                'lock_version' => $lockedVehicle->lock_version + 1,
            ])->touch();

            $this->mediaRetirement->retire(paths: [$path], directory: null);
        });
    }
}
