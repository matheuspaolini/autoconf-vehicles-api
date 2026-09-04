<?php

namespace App\Queries;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class VehicleIndexQuery
{
    private const SORTABLE_COLUMNS = ['km' => 'km', 'valor_venda' => 'valor_venda'];

    public function __construct(
        private readonly VehicleRead $vehicleRead,
    ) {}

    public function paginate(array $filters, User $actor): LengthAwarePaginator
    {
        $query = $this->vehicleRead->forCatalog();
        if (($filters['scope'] ?? null) === 'mine') {
            $query->whereBelongsTo($actor, 'owner');
        }
        foreach (['marca', 'modelo', 'placa'] as $field) {
            if (filled($filters[$field] ?? null)) {
                $this->like($query, $field, $filters[$field]);
            }
        }
        if (filled($filters['q'] ?? null)) {
            $needle = $this->escapeLike($filters['q']);
            $query->where(fn (Builder $q) => $q->whereRaw("LOWER(placa) LIKE ? ESCAPE '\\'", ['%'.mb_strtolower($needle).'%'])->orWhereRaw("LOWER(marca) LIKE ? ESCAPE '\\'", ['%'.mb_strtolower($needle).'%'])->orWhereRaw("LOWER(modelo) LIKE ? ESCAPE '\\'", ['%'.mb_strtolower($needle).'%']));
        }
        $terms = array_filter(explode(',', (string) ($filters['sort'] ?? '')));
        if ($terms === []) {
            $query->orderByDesc('created_at')->orderByDesc('id');
        } else {
            foreach ($terms as $term) {
                $name = ltrim($term, '-');
                $query->orderBy(self::SORTABLE_COLUMNS[$name], str_starts_with($term, '-') ? 'desc' : 'asc');
            } $query->orderBy('id');
        }

        return $query->paginate(min((int) ($filters['per_page'] ?? 15), 100))->withQueryString();
    }

    private function like(Builder $query, string $column, string $value): void
    {
        $query->whereRaw("LOWER({$column}) LIKE ? ESCAPE '\\'", ['%'.mb_strtolower($this->escapeLike($value)).'%']);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
