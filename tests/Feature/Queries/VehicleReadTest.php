<?php

namespace Tests\Feature\Queries;

use App\Models\User;
use App\Modules\Vehicles\Application\Data\VehicleCatalogCriteria;
use App\Modules\Vehicles\Application\Query\GetVehicleDetailHandler;
use App\Modules\Vehicles\Application\Query\ListVehiclesHandler;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\VehicleImageRecord as VehicleImage;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\VehicleRecord as Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VehicleReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_projection_loads_list_data_without_follow_up_queries(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();
        VehicleImage::factory()->for($vehicle)->cover()->create();
        $criteria = new VehicleCatalogCriteria([], null, null, []);

        $page = app(ListVehiclesHandler::class)->handle($criteria);
        $listed = $page->items[0];

        $this->assertSame($vehicle->id, $listed->id);
        $this->assertSame($owner->id, $listed->owner->id);
        $this->assertDoesNotQuery(static fn () => $listed->coverImage?->path);
    }

    public function test_detail_projection_is_fresh_and_orders_the_cover_first(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();
        VehicleImage::factory()->for($vehicle)->create();
        $cover = VehicleImage::factory()->for($vehicle)->cover()->create();

        Vehicle::query()->whereKey($vehicle)->update(['marca' => 'Ford']);
        $detail = app(GetVehicleDetailHandler::class)->handle((int) $vehicle->getKey());

        $this->assertNotNull($detail);
        $this->assertSame('Ford', $detail->details->brand);
        $this->assertSame($cover->id, $detail->images[0]->id);
        $this->assertDoesNotQuery(static fn () => $detail->images[0]->path);
    }

    public function test_detail_returns_null_for_an_unknown_vehicle(): void
    {
        $this->assertNull(app(GetVehicleDetailHandler::class)->handle(999));
    }

    private function assertDoesNotQuery(callable $read): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $read();
            $this->assertSame([], DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }
}
