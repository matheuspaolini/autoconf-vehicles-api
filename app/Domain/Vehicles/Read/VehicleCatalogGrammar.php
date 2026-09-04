<?php

namespace App\Domain\Vehicles\Read;

use App\Models\User;
use App\Models\Vehicle;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

final class VehicleCatalogGrammar
{
    public const DEFAULT_PER_PAGE = 15;

    public const MAX_PER_PAGE = 100;

    private const MIN_PAGE = 1;

    private const MAX_QUERY_TEXT_LENGTH = 100;

    private const MAX_PLATE_LENGTH = 7;

    private const MINE_SCOPE = 'mine';

    private const SORT_SEPARATOR = ',';

    private const DESCENDING_PREFIX = '-';

    /** @var list<string> */
    private const FILTER_FIELDS = ['marca', 'modelo', 'placa'];

    /** @var array<string, string> */
    private const SORTABLE_COLUMNS = ['km' => 'km', 'valor_venda' => 'valor_venda'];

    /** @return array<string, list<Closure|string|object>> */
    public function validationRules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:'.self::MAX_QUERY_TEXT_LENGTH],
            'marca' => ['nullable', 'string', 'max:'.self::MAX_QUERY_TEXT_LENGTH],
            'modelo' => ['nullable', 'string', 'max:'.self::MAX_QUERY_TEXT_LENGTH],
            'placa' => ['nullable', 'string', 'max:'.self::MAX_PLATE_LENGTH],
            'sort' => ['nullable', 'string', 'max:'.self::MAX_QUERY_TEXT_LENGTH, $this->sortValidationRule()],
            'scope' => ['nullable', Rule::in([self::MINE_SCOPE])],
            'page' => ['nullable', 'integer', 'min:'.self::MIN_PAGE],
            'per_page' => ['nullable', 'integer', 'min:'.self::MIN_PAGE, 'max:'.self::MAX_PER_PAGE],
        ];
    }

    /** @param array<string, mixed> $validated */
    public function criteria(array $validated): VehicleCatalogCriteria
    {
        $filters = [];

        foreach (self::FILTER_FIELDS as $field) {
            $filters[$field] = $validated[$field] ?? null;
        }

        return new VehicleCatalogCriteria(
            filters: $filters,
            search: $validated['q'] ?? null,
            isMine: ($validated['scope'] ?? null) === self::MINE_SCOPE,
            sortTerms: $this->sortTerms((string) ($validated['sort'] ?? '')),
            perPage: (int) ($validated['per_page'] ?? self::DEFAULT_PER_PAGE),
            page: isset($validated['page']) ? (int) $validated['page'] : null,
        );
    }

    /** @param Builder<Vehicle> $vehicles */
    public function apply(Builder $vehicles, VehicleCatalogCriteria $criteria, User $viewer): Builder
    {
        $vehicles->when(
            $criteria->isMine,
            static fn (Builder $query): Builder => $query->whereBelongsTo($viewer, 'owner'),
        );

        foreach (self::FILTER_FIELDS as $field) {
            $filter = $criteria->filter($field);

            $vehicles->when(
                filled($filter),
                fn (Builder $query): Builder => $this->applyLikeFilter($query, $field, (string) $filter),
            );
        }

        $vehicles->when(
            filled($criteria->search),
            fn (Builder $query): Builder => $this->applySearch($query, (string) $criteria->search),
        );

        if ($criteria->sortTerms === []) {
            return $vehicles->orderByDesc('created_at')->orderByDesc('id');
        }

        foreach ($criteria->sortTerms as $term) {
            $sortName = \ltrim($term, self::DESCENDING_PREFIX);
            $direction = \str_starts_with($term, self::DESCENDING_PREFIX) ? 'desc' : 'asc';
            $vehicles->orderBy(self::SORTABLE_COLUMNS[$sortName], $direction);
        }

        return $vehicles->orderBy('id');
    }

    private function sortValidationRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            foreach ($this->sortTerms((string) $value) as $term) {
                $sortName = \ltrim($term, self::DESCENDING_PREFIX);

                if (! \array_key_exists($sortName, self::SORTABLE_COLUMNS)) {
                    $fail('The selected sort field is invalid.');

                    return;
                }
            }
        };
    }

    /** @return list<string> */
    private function sortTerms(string $sort): array
    {
        return \array_values(\array_filter(\explode(self::SORT_SEPARATOR, $sort)));
    }

    /** @param Builder<Vehicle> $vehicles */
    private function applyLikeFilter(Builder $vehicles, string $column, string $value): Builder
    {
        return $vehicles->whereRaw(
            "LOWER({$column}) LIKE ? ESCAPE '\\'",
            ['%'.\mb_strtolower($this->escapeLike($value)).'%'],
        );
    }

    /** @param Builder<Vehicle> $vehicles */
    private function applySearch(Builder $vehicles, string $search): Builder
    {
        $escapedSearch = '%'.\mb_strtolower($this->escapeLike($search)).'%';

        return $vehicles->where(function (Builder $query) use ($escapedSearch): void {
            $query->whereRaw("LOWER(placa) LIKE ? ESCAPE '\\'", [$escapedSearch])
                ->orWhereRaw("LOWER(marca) LIKE ? ESCAPE '\\'", [$escapedSearch])
                ->orWhereRaw("LOWER(modelo) LIKE ? ESCAPE '\\'", [$escapedSearch]);
        });
    }

    private function escapeLike(string $value): string
    {
        return \str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
