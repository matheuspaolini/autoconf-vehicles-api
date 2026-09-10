<?php

namespace App\Modules\Vehicles\Application\Port;

use App\Modules\Vehicles\Application\Data\PageData;
use App\Modules\Vehicles\Application\Data\Pagination;
use App\Modules\Vehicles\Application\Data\VehicleCatalogCriteria;
use App\Modules\Vehicles\Application\Data\VehicleData;
use App\Modules\Vehicles\Application\Data\VehicleImagePageData;
use App\Modules\Vehicles\Domain\ValueObject\VehicleId;

interface VehicleReadGateway
{
    public function paginate(VehicleCatalogCriteria $criteria): PageData;

    public function detail(VehicleId $id): ?VehicleData;

    public function gallery(VehicleId $id, Pagination $pagination): ?VehicleImagePageData;
}
