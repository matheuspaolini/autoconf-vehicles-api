<?php

namespace App\Models;

use App\Enums\FuelType;
use App\Enums\Transmission;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    protected $fillable = [
        'placa', 'chassi', 'marca', 'modelo', 'versao', 'valor_venda', 'cor', 'km', 'cambio', 'combustivel',
    ];

    protected function casts(): array
    {
        return [
            'valor_venda' => 'decimal:2',
            'km' => 'integer',
            'cambio' => Transmission::class,
            'combustivel' => FuelType::class,
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class);
    }

    public function coverImage(): HasOne
    {
        return $this->hasOne(VehicleImage::class)->where('is_cover', true);
    }
}
