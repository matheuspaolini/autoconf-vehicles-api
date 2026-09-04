<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaCleanupTask extends Model
{
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
