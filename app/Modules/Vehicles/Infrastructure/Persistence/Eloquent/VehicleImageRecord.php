<?php

namespace App\Modules\Vehicles\Infrastructure\Persistence\Eloquent;

use Database\Factories\VehicleImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $vehicle_id
 * @property string $path
 * @property bool $is_cover
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class VehicleImageRecord extends Model
{
    /** @use HasFactory<VehicleImageFactory> */
    use HasFactory;

    protected $fillable = [
        'path',
    ];

    protected static function newFactory(): VehicleImageFactory
    {
        return VehicleImageFactory::new();
    }

    protected $table = 'vehicle_images';

    protected function casts(): array
    {
        return ['is_cover' => 'boolean'];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(VehicleRecord::class, 'vehicle_id');
    }

    public function vehicleRecord(): BelongsTo
    {
        return $this->vehicle();
    }
}
