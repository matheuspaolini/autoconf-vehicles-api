<?php

namespace App\Modules\Vehicles\Infrastructure\Persistence\Eloquent;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $vehicle_id
 * @property string $idempotency_key
 * @property string $request_hash
 * @property string $status
 * @property int|null $response_status
 * @property array<string, mixed>|null $response_body
 * @property string|null $response_etag
 * @property Carbon $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class VehicleUploadRequestRecord extends Model
{
    use HasFactory;

    protected $table = 'vehicle_upload_requests';

    protected $fillable = [
        'user_id', 'vehicle_id', 'idempotency_key', 'request_hash', 'status',
        'response_status', 'response_body', 'response_etag', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'response_body' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(VehicleRecord::class, 'vehicle_id');
    }
}
