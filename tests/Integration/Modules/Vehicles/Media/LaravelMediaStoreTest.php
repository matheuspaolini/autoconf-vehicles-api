<?php

namespace Tests\Integration\Modules\Vehicles\Media;

use App\Modules\Vehicles\Application\Port\UploadSource;
use App\Modules\Vehicles\Domain\ValueObject\VehicleId;
use App\Modules\Vehicles\Infrastructure\Media\LaravelMediaStore;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class LaravelMediaStoreTest extends TestCase
{
    public function test_it_stores_and_deletes_upload_streams_on_the_configured_disk(): void
    {
        Storage::fake('public');
        $store = new LaravelMediaStore(Storage::disk('public'));
        $source = new class implements UploadSource
        {
            public function originalName(): string
            {
                return 'vehicle.png';
            }

            public function mediaType(): string
            {
                return 'image/png';
            }

            public function sizeInBytes(): int
            {
                return 5;
            }

            public function contentHash(): string
            {
                return \hash('sha256', 'image');
            }

            public function openStream()
            {
                $stream = \fopen('php://memory', 'r+');
                \fwrite($stream, 'image');
                \rewind($stream);

                return $stream;
            }
        };

        $stored = $store->store(new VehicleId(12), $source);
        Storage::disk('public')->assertExists($stored->path);

        $store->delete([$stored->path]);

        Storage::disk('public')->assertMissing($stored->path);
        self::assertMatchesRegularExpression('#^vehicles/12/[a-f0-9]{40}\.png$#', $stored->path);
    }
}
