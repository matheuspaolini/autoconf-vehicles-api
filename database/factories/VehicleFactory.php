<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Vehicles\Domain\Enum\FuelType;
use App\Modules\Vehicles\Domain\Enum\Transmission;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\VehicleRecord as Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vehicle> */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        $owner = User::factory();

        return [
            'user_id' => $owner, 'created_by' => $owner, 'updated_by' => $owner,
            'placa' => fake()->unique()->regexify('[A-Z]{3}[0-9][A-Z0-9][0-9]{2}'),
            'chassi' => fake()->unique()->regexify('[A-HJ-NPR-Z0-9]{17}'),
            'marca' => fake()->randomElement(['Chevrolet', 'Fiat', 'Honda', 'Toyota']), 'modelo' => fake()->word(), 'versao' => 'LX',
            'valor_venda' => fake()->randomFloat(2, 30000, 180000), 'cor' => fake()->colorName(), 'km' => fake()->numberBetween(0, 150000),
            'cambio' => fake()->randomElement(Transmission::cases()), 'combustivel' => fake()->randomElement(FuelType::cases()),
        ];
    }

    public function forOwner(User $owner): static
    {
        return $this->state(fn () => ['user_id' => $owner->id, 'created_by' => $owner->id, 'updated_by' => $owner->id]);
    }
}
