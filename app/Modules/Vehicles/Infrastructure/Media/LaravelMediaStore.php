<?php

namespace App\Modules\Vehicles\Infrastructure\Media;

use App\Modules\Vehicles\Application\Data\StoredImage;
use App\Modules\Vehicles\Application\Port\MediaStore;
use App\Modules\Vehicles\Application\Port\UploadSource;
use App\Modules\Vehicles\Domain\ValueObject\VehicleId;
use Illuminate\Contracts\Filesystem\Filesystem;
use RuntimeException;

final readonly class LaravelMediaStore implements MediaStore
{
    public function __construct(private Filesystem $disk) {}

    public function store(VehicleId $vehicleId, UploadSource $source): StoredImage
    {
        $extension = $this->extensionFor($source->mediaType());
        $path = "vehicles/{$vehicleId->value}/".\bin2hex(\random_bytes(20)).".{$extension}";
        $stored = $this->disk->put($path, $source->openStream());

        if (! $stored) {
            throw new RuntimeException('Vehicle image could not be stored.');
        }

        return new StoredImage($path);
    }

    public function delete(array $paths): void
    {
        if ($paths !== []) {
            $this->disk->delete($paths);
        }
    }

    public function deleteDirectory(string $directory): void
    {
        if (! $this->disk->deleteDirectory($directory)) {
            throw new RuntimeException("Unable to delete media directory: {$directory}");
        }
    }

    private function extensionFor(string $mediaType): string
    {
        return match ($mediaType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new RuntimeException("Unsupported image media type: {$mediaType}"),
        };
    }
}
