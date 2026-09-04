<?php

namespace App\Http\Requests;

use App\Domain\Vehicles\Read\VehicleCatalogGrammar;
use App\Models\Vehicle;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Foundation\Http\FormRequest;

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
        return $this->user()?->can('viewAny', Vehicle::class) ?? false;
    }

    public function rules(VehicleCatalogGrammar $grammar): array
    {
        return $grammar->validationRules();
    }
}
