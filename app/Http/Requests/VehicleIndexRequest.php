<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class VehicleIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Vehicle::class) ?? false;
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:100'], 'marca' => ['nullable', 'string', 'max:100'], 'modelo' => ['nullable', 'string', 'max:100'], 'placa' => ['nullable', 'string', 'max:7'], 'sort' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach (array_filter(explode(',', (string) $this->input('sort'))) as $term) {
                if (! in_array(ltrim($term, '-'), ['km', 'valor_venda'], true)) {
                    $validator->errors()->add('sort', 'The selected sort field is invalid.');
                }
            }
        }];
    }
}
