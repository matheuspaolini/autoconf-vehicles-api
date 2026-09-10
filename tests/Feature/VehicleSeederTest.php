<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\VehicleImageRecord as VehicleImage;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\VehicleRecord as Vehicle;
use Database\Seeders\VehicleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VehicleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_sixty_vehicles_with_catalog_wide_unique_images(): void
    {
        Storage::fake('public');
        Http::fake(fn (Request $request) => $this->responseFor($request));
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        (new VehicleSeeder)->run($admin, $user);

        $this->assertSame(60, Vehicle::query()->count());
        $this->assertSame(240, VehicleImage::query()->count());

        $contentHashes = [];

        Vehicle::query()->with('images')->each(function (Vehicle $vehicle) use (&$contentHashes): void {
            $this->assertCount(4, $vehicle->images);
            $this->assertSame(1, $vehicle->images->where('is_cover', true)->count());

            foreach ($vehicle->images as $image) {
                $this->assertStringNotContainsString('placeholder', $image->path);
                $contents = Storage::disk('public')->get($image->path);
                $contentHashes[] = \hash('sha256', $contents);
            }
        });

        $this->assertCount(240, \array_unique($contentHashes));
    }

    public function test_it_leaves_galleries_empty_when_commons_is_unavailable(): void
    {
        Storage::fake('public');
        Http::fake(['commons.wikimedia.org/*' => Http::response(status: 503)]);
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        (new VehicleSeeder)->run($admin, $user);

        $this->assertSame(60, Vehicle::query()->count());
        $this->assertSame(0, VehicleImage::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        Http::assertSentCount(120);
    }

    private function responseFor(Request $request): mixed
    {
        if (\str_starts_with($request->url(), 'https://commons.wikimedia.org/w/api.php')) {
            $search = (string) ($request->data()['gsrsearch'] ?? 'vehicle');
            $searchHash = \hash('sha256', $search);
            $pages = [];

            for ($index = 0; $index < 7; $index++) {
                $shared = $index === 0;
                $downloadUrl = $shared
                    ? 'https://images.example/shared.jpg'
                    : "https://images.example/{$searchHash}/{$index}.jpg";
                $sourceUrl = $shared
                    ? 'https://upload.example/shared.jpg'
                    : "https://upload.example/{$searchHash}/{$index}.jpg";
                $pages[$index + 1] = [
                    'index' => $index + 1,
                    'title' => "File:{$search}-{$index}.jpg",
                    'imageinfo' => [[
                        'thumburl' => $downloadUrl,
                        'url' => $sourceUrl,
                        'mime' => 'image/jpeg',
                    ]],
                ];
            }

            return Http::response(['query' => ['pages' => $pages]]);
        }

        $body = \str_ends_with($request->url(), '/1.jpg')
            ? 'duplicate-body-under-different-urls'
            : "unique-body:{$request->url()}";

        return Http::response($body, 200, ['Content-Type' => 'image/jpeg']);
    }
}
