<?php

namespace Tests\Feature\Queries;

use App\Domain\Vehicles\Read\VehicleCatalogGrammar;
use App\Domain\Vehicles\Read\VehicleDetailRepresentation;
use App\Domain\Vehicles\Read\VehicleListRepresentation;
use App\Domain\Vehicles\Read\VehicleRepresentationRead;
use App\Http\Resources\VehicleDetailResource;
use App\Http\Resources\VehicleListResource;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VehicleReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_query_loads_list_relations_without_resource_queries(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();
        VehicleImage::factory()->for($vehicle)->cover()->create();

        $criteria = app(VehicleCatalogGrammar::class)->criteria([]);
        $listed = app(VehicleRepresentationRead::class)->catalog($criteria, $owner)->getCollection()->first();

        $this->assertInstanceOf(VehicleListRepresentation::class, $listed);
        $this->assertSame($vehicle->id, $listed->id);
        $this->assertSame($owner->id, $listed->ownerId);
        $this->assertResourceDoesNotQuery(fn () => VehicleListResource::make($listed)->resolve($this->requestFor($owner)));
    }

    public function test_detail_returns_a_fresh_vehicle_without_loading_the_gallery(): void
    {
        $owner = User::factory()->create();
        $vehicle = Vehicle::factory()->forOwner($owner)->create();
        VehicleImage::factory()->for($vehicle)->cover()->create();
        $stale = Vehicle::query()->findOrFail($vehicle->getKey());

        Vehicle::query()->whereKey($vehicle)->update(['marca' => 'Ford']);

        $detail = app(VehicleRepresentationRead::class)->detail((int) $vehicle->getKey(), $owner);

        $this->assertInstanceOf(VehicleDetailRepresentation::class, $detail);
        $this->assertSame('Ford', $detail->vehicle->marca);
        $this->assertNotSame($stale->marca, $detail->vehicle->marca);
        $this->assertSame($owner->id, $detail->audit->createdById);
        $this->assertSame($owner->id, $detail->audit->updatedById);
        $this->assertResourceDoesNotQuery(fn () => VehicleDetailResource::make($detail)->resolve($this->requestFor($owner)));
    }

    public function test_detail_throws_for_an_unknown_vehicle(): void
    {
        $this->expectException(ModelNotFoundException::class);

        app(VehicleRepresentationRead::class)->detail(999, User::factory()->create());
    }

    private function assertResourceDoesNotQuery(callable $resolve): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $resolve();

            $this->assertSame([], DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    private function requestFor(User $user): Request
    {
        $request = Request::create('/');
        $request->setUserResolver(fn (): User => $user);

        return $request;
    }
}
