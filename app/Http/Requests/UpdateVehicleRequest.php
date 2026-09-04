<?php

namespace App\Http\Requests;

use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    use NormalizesVehicleInput;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('vehicle')) ?? false;
    }

    public function rules(): array
    {
        /** @var Vehicle $vehicle */ $vehicle = $this->route('vehicle');

        return ['placa' => ['required', 'string', 'regex:/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', Rule::unique('vehicles', 'placa')->ignore($vehicle)], 'chassi' => ['required', 'string', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/', Rule::unique('vehicles', 'chassi')->ignore($vehicle)], 'marca' => ['required', 'string', 'max:100'], 'modelo' => ['required', 'string', 'max:100'], 'versao' => ['required', 'string', 'max:100'], 'valor_venda' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999999.99'], 'cor' => ['required', 'string', 'max:100'], 'km' => ['required', 'integer', 'min:0'], 'cambio' => ['required', Rule::enum(Transmission::class)], 'combustivel' => ['required', Rule::enum(FuelType::class)]];
    }
}
