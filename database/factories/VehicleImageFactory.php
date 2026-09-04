<?php

namespace Database\Factories;

use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VehicleImage> */
class VehicleImageFactory extends Factory
{
    protected $model = VehicleImage::class;

    public function definition(): array
    {
        return ['vehicle_id' => Vehicle::factory(), 'path' => 'vehicles/'.fake()->numberBetween(1, 999).'/'.fake()->uuid().'.png', 'is_cover' => false];
    }

    public function cover(): static
    {
        return $this->state(['is_cover' => true]);
    }
}
