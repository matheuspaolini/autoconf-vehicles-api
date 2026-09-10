<?php

namespace App\Modules\Vehicles\Infrastructure\Persistence\Eloquent;

use App\Modules\Vehicles\Application\Data\PageData;
use App\Modules\Vehicles\Application\Data\Pagination;
use App\Modules\Vehicles\Application\Data\VehicleCatalogCriteria;
use App\Modules\Vehicles\Application\Data\VehicleData;
use App\Modules\Vehicles\Application\Data\VehicleImagePageData;
use App\Modules\Vehicles\Application\Port\VehicleReadGateway;
use App\Modules\Vehicles\Domain\ValueObject\VehicleId;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final readonly class EloquentVehicleReadGateway implements VehicleReadGateway
{
    private const FILTER_COLUMNS = ['marca', 'modelo', 'placa'];

    private const SORT_COLUMNS = ['km' => 'km', 'valor_venda' => 'valor_venda'];

    public function __construct(private VehicleMapper $mapper) {}

    public function paginate(VehicleCatalogCriteria $criteria): PageData
    {
        $query = VehicleRecord::query()->with(['owner', 'creator', 'updater', 'coverImage', 'images']);

        if ($criteria->ownerId !== null) {
            $query->where('user_id', $criteria->ownerId);
        }

        foreach (self::FILTER_COLUMNS as $field) {
            $value = $criteria->filters[$field] ?? null;

            if ($value !== null && $value !== '') {
                $this->applyLikeFilter($query, $field, $value);
            }
        }

        if ($criteria->search !== null && $criteria->search !== '') {
            $this->applySearch($query, $criteria->search);
        }

        $this->applySort($query, $criteria->sortTerms);

        return $this->pageData($query->paginate($criteria->perPage, page: $criteria->page));
    }

    public function detail(VehicleId $id): ?VehicleData
    {
        $record = VehicleRecord::query()
            ->with(['owner', 'creator', 'updater', 'coverImage', 'images'])
            ->find($id->value);

        return $record instanceof VehicleRecord ? $this->mapper->toData($record) : null;
    }

    public function gallery(VehicleId $id, Pagination $pagination): ?VehicleImagePageData
    {
        $record = VehicleRecord::query()->find($id->value);

        if (! $record instanceof VehicleRecord) {
            return null;
        }

        $paginator = $record->images()->orderByDesc('is_cover')->orderBy('id')->paginate($pagination->perPage, page: $pagination->page);

        return new VehicleImagePageData(
            page: $this->pageData($paginator, $this->mapper->toImageData(...)),
            vehicleVersion: $record->lock_version,
        );
    }

    /** @param Builder<VehicleRecord> $query */
    private function applyLikeFilter(Builder $query, string $field, string $value): void
    {
        $query->whereRaw("LOWER({$field}) LIKE ? ESCAPE '\\'", ['%'.\mb_strtolower($this->escapeLike($value)).'%']);
    }

    /** @param Builder<VehicleRecord> $query */
    private function applySearch(Builder $query, string $value): void
    {
        $escaped = '%'.\mb_strtolower($this->escapeLike($value)).'%';
        $query->where(static fn (Builder $nested): Builder => $nested
            ->whereRaw("LOWER(placa) LIKE ? ESCAPE '\\'", [$escaped])
            ->orWhereRaw("LOWER(marca) LIKE ? ESCAPE '\\'", [$escaped])
            ->orWhereRaw("LOWER(modelo) LIKE ? ESCAPE '\\'", [$escaped]));
    }

    /** @param Builder<VehicleRecord> $query @param list<string> $terms */
    private function applySort(Builder $query, array $terms): void
    {
        if ($terms === []) {
            $query->orderByDesc('created_at')->orderByDesc('id');

            return;
        }

        foreach ($terms as $term) {
            $name = \ltrim($term, '-');
            $query->orderBy(self::SORT_COLUMNS[$name], \str_starts_with($term, '-') ? 'desc' : 'asc');
        }

        $query->orderBy('id');
    }

    private function escapeLike(string $value): string
    {
        return \str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function pageData(LengthAwarePaginator $paginator, ?callable $map = null): PageData
    {
        $items = $paginator->getCollection()->map($map ?? $this->mapper->toData(...))->values()->all();

        return new PageData(
            items: $items,
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            firstPageUrl: $paginator->url(1),
            lastPageUrl: $paginator->url($paginator->lastPage()),
            nextPageUrl: $paginator->nextPageUrl(),
            previousPageUrl: $paginator->previousPageUrl(),
            from: $paginator->firstItem(),
            to: $paginator->lastItem(),
            path: $paginator->path(),
            links: $paginator->linkCollection()->toArray(),
        );
    }
}
