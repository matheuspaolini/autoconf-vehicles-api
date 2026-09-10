<?php

namespace App\Modules\Vehicles\Presentation\Http\Requests;

use App\Modules\Vehicles\Application\Data\VehicleCatalogCriteria;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

#[QueryParameter(
    'sort',
    'Comma-separated sort terms: km or valor_venda. Prefix a term with - for descending order, for example km,-valor_venda.',
    type: 'string',
    example: 'km,-valor_venda',
)]
#[QueryParameter(
    'scope',
    'Restricts the catalog. Use mine to return vehicles registered by the authenticated user.',
    type: 'string',
    example: 'mine',
)]
class VehicleIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'marca' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'placa' => ['nullable', 'string', 'max:7'],
            'sort' => ['nullable', 'string', 'max:100', 'regex:/^-?(km|valor_venda)(,-?(km|valor_venda))*$/'],
            'scope' => ['nullable', Rule::in(['mine'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.VehicleCatalogCriteria::MAXIMUM_PER_PAGE],
        ];
    }

    public function criteria(): VehicleCatalogCriteria
    {
        $validated = $this->validated();
        $filters = [
            'marca' => $validated['marca'] ?? null,
            'modelo' => $validated['modelo'] ?? null,
            'placa' => $validated['placa'] ?? null,
        ];
        $sort = (string) ($validated['sort'] ?? '');

        return new VehicleCatalogCriteria(
            filters: $filters,
            search: $validated['q'] ?? null,
            ownerId: ($validated['scope'] ?? null) === 'mine' ? (int) $this->user()->getKey() : null,
            sortTerms: $sort === '' ? [] : \explode(',', $sort),
            perPage: (int) ($validated['per_page'] ?? VehicleCatalogCriteria::DEFAULT_PER_PAGE),
            page: isset($validated['page']) ? (int) $validated['page'] : null,
        );
    }
}
