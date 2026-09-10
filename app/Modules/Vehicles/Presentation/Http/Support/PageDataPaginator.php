<?php

namespace App\Modules\Vehicles\Presentation\Http\Support;

use App\Modules\Vehicles\Application\Data\PageData;
use Illuminate\Pagination\LengthAwarePaginator;

final class PageDataPaginator
{
    public function make(PageData $page): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: $page->items,
            total: $page->total,
            perPage: $page->perPage,
            currentPage: $page->currentPage,
            options: ['path' => $page->path, 'pageName' => 'page'],
        );
    }
}
