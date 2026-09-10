<?php

namespace App\Modules\Vehicles\Application\Query;

use App\Modules\Vehicles\Application\Data\PageData;
use App\Modules\Vehicles\Application\Data\VehicleCatalogCriteria;
use App\Modules\Vehicles\Application\Port\VehicleReadGateway;

final readonly class ListVehiclesHandler
{
    public function __construct(private VehicleReadGateway $vehicles) {}

    public function handle(VehicleCatalogCriteria $criteria): PageData
    {
        return $this->vehicles->paginate($criteria);
    }
}
