<?php

namespace App\Http\Controllers;

use App\Actions\DeleteVehicle;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Http\Requests\VehicleIndexRequest;
use App\Http\Resources\VehicleDetailResource;
use App\Http\Resources\VehicleListResource;
use App\Models\Vehicle;
use App\Queries\VehicleIndexQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VehicleController extends Controller
{
    public function index(VehicleIndexRequest $request, VehicleIndexQuery $query): AnonymousResourceCollection
    {
        return VehicleListResource::collection($query->paginate($request->validated()));
    }

    public function store(StoreVehicleRequest $request)
    {
        $actor = $request->user();
        $vehicle = new Vehicle($request->validated());
        $vehicle->user_id = $actor->id;
        $vehicle->created_by = $actor->id;
        $vehicle->updated_by = $actor->id;
        $vehicle->save();

        return VehicleDetailResource::make($vehicle->load(['owner', 'creator', 'updater', 'images', 'coverImage']))->response()->setStatusCode(201);
    }

    public function show(Vehicle $vehicle): VehicleDetailResource
    {
        $this->authorize('view', $vehicle);

        return VehicleDetailResource::make($vehicle->load(['owner', 'creator', 'updater', 'images' => fn ($q) => $q->orderByDesc('is_cover')->orderBy('id'), 'coverImage']));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): VehicleDetailResource
    {
        $vehicle->fill($request->validated());
        $vehicle->updated_by = $request->user()->id;
        $vehicle->save();

        return VehicleDetailResource::make($vehicle->refresh()->load(['owner', 'creator', 'updater', 'images', 'coverImage']));
    }

    public function destroy(Vehicle $vehicle, DeleteVehicle $action)
    {
        $this->authorize('delete', $vehicle);
        $action->execute($vehicle);

        return response()->noContent();
    }
}
