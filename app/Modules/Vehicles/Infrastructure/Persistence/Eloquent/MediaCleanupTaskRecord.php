<?php

namespace App\Modules\Vehicles\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property list<string> $paths
 * @property string|null $directory
 * @property int $attempts
 * @property string|null $last_error
 * @property Carbon|null $last_dispatched_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class MediaCleanupTaskRecord extends Model
{
    protected $table = 'media_cleanup_tasks';

    protected $fillable = [
        'paths', 'directory', 'attempts', 'last_error', 'last_dispatched_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'paths' => 'array',
            'last_dispatched_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
