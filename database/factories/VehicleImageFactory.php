<?php

namespace Database\Factories;

use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\VehicleImageRecord as VehicleImage;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\VehicleRecord as Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VehicleImage> */
class VehicleImageFactory extends Factory
{
    protected $model = VehicleImage::class;

    public function definition(): array
    {
        return ['vehicle_id' => Vehicle::factory(), 'path' => 'vehicles/'.fake()->numberBetween(1, 999).'/'.fake()->uuid().'.png'];
    }

    public function cover(): static
    {
        return $this->state(['is_cover' => true]);
    }
}
