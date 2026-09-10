<?php

namespace App\Modules\Vehicles\Infrastructure\Persistence\Eloquent;

use App\Models\User;
use App\Modules\Vehicles\Domain\Enum\FuelType;
use App\Modules\Vehicles\Domain\Enum\Transmission;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $created_by
 * @property int $updated_by
 * @property string $placa
 * @property string $chassi
 * @property string $marca
 * @property string $modelo
 * @property string $versao
 * @property string $valor_venda
 * @property string $cor
 * @property int $km
 * @property Transmission $cambio
 * @property FuelType $combustivel
 * @property int $lock_version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class VehicleRecord extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    protected $attributes = [
        'lock_version' => 1,
    ];

    protected $table = 'vehicles';

    protected $fillable = [
        'placa', 'chassi', 'marca', 'modelo', 'versao', 'valor_venda', 'cor', 'km', 'cambio', 'combustivel',
    ];

    protected static function newFactory(): VehicleFactory
    {
        return VehicleFactory::new();
    }

    protected function casts(): array
    {
        return [
            'valor_venda' => 'decimal:2',
            'km' => 'integer',
            'lock_version' => 'integer',
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
        return $this->hasMany(VehicleImageRecord::class, 'vehicle_id');
    }

    public function coverImage(): HasOne
    {
        return $this->hasOne(VehicleImageRecord::class, 'vehicle_id')->where('is_cover', true);
    }
}
