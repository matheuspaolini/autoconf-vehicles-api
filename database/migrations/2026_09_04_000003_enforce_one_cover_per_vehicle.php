<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const COVER_INDEX = 'vehicle_images_one_cover_per_vehicle';

    public function up(): void
    {
        $this->ensureSqlite();
        $this->repairLegacyGalleries();

        DB::statement(
            'CREATE UNIQUE INDEX '.self::COVER_INDEX.'
             ON vehicle_images (vehicle_id)
             WHERE is_cover = 1'
        );
    }

    public function down(): void
    {
        $this->ensureSqlite();

        DB::statement('DROP INDEX IF EXISTS '.self::COVER_INDEX);
    }

    private function ensureSqlite(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new RuntimeException('The vehicle gallery cover invariant requires SQLite.');
        }
    }

    private function repairLegacyGalleries(): void
    {
        $vehicleIds = DB::table('vehicle_images')
            ->select('vehicle_id')
            ->groupBy('vehicle_id')
            ->orderBy('vehicle_id')
            ->pluck('vehicle_id');

        foreach ($vehicleIds as $vehicleId) {
            $imageIds = DB::table('vehicle_images')
                ->where('vehicle_id', $vehicleId)
                ->orderBy('id')
                ->pluck('id');

            if (DB::table('vehicle_images')
                ->where('vehicle_id', $vehicleId)
                ->where('is_cover', true)
                ->count() === 1) {
                continue;
            }

            DB::table('vehicle_images')
                ->where('vehicle_id', $vehicleId)
                ->update(['is_cover' => false]);

            DB::table('vehicle_images')
                ->where('id', $imageIds->first())
                ->update(['is_cover' => true]);
        }
    }
};
