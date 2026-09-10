<?php

namespace App\Modules\Vehicles\Presentation\Http\Requests;

use Illuminate\Support\Str;

trait NormalizesVehicleInput
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'placa' => Str::upper(\trim((string) $this->input('placa'))),
            'chassi' => Str::upper(\trim((string) $this->input('chassi'))),
            'marca' => \trim((string) $this->input('marca')),
            'modelo' => \trim((string) $this->input('modelo')),
            'versao' => \trim((string) $this->input('versao')),
            'cor' => \trim((string) $this->input('cor')),
        ]);
    }
}
