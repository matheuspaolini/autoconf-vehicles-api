<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const COVER_INDEX = 'vehicle_images_one_cover_per_vehicle';

    public function up(): void
    {
        DB::statement(
            'CREATE UNIQUE INDEX '.self::COVER_INDEX.'
             ON vehicle_images (vehicle_id)
             WHERE is_cover'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS '.self::COVER_INDEX);
    }
};
