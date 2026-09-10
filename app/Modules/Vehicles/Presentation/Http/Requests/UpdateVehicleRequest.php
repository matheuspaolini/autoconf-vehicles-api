<?php

namespace App\Modules\Vehicles\Presentation\Http\Requests;

use App\Modules\Vehicles\Domain\Enum\FuelType;
use App\Modules\Vehicles\Domain\Enum\Transmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    use NormalizesVehicleInput;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['placa' => ['required', 'string', 'regex:/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/'], 'chassi' => ['required', 'string', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/'], 'marca' => ['required', 'string', 'max:100'], 'modelo' => ['required', 'string', 'max:100'], 'versao' => ['required', 'string', 'max:100'], 'valor_venda' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999999.99'], 'cor' => ['required', 'string', 'max:100'], 'km' => ['required', 'integer', 'min:0'], 'cambio' => ['required', Rule::enum(Transmission::class)], 'combustivel' => ['required', Rule::enum(FuelType::class)]];
    }
}
