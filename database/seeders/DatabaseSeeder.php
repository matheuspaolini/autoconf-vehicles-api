<?php

namespace Database\Seeders;

use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Administrator', 'email' => 'admin@example.com', 'password' => 'password']);
        $user = User::factory()->create(['name' => 'Regular User', 'email' => 'user@example.com', 'password' => 'password']);
        $vehicles = [
            ['ABC1D23', '9BWZZZ377VT004251', 'Chevrolet', 'Onix', 'LT', 78900, 'Prata', 25000, Transmission::Automatic, FuelType::Flex],
            ['DEF2G34', '1HGCM82633A004352', 'Fiat', 'Pulse', 'Drive', 94500, 'Branco', 18000, Transmission::Manual, FuelType::Gasoline],
            ['GHI3J45', 'JH4KA8270MC004453', 'Honda', 'Civic', 'Touring', 158900, 'Cinza', 41000, Transmission::Automatic, FuelType::Ethanol],
            ['JKL4M56', 'WVWZZZ1JZXW004554', 'Toyota', 'Corolla', 'GLi', 132500, 'Preto', 35000, Transmission::Automatic, FuelType::Hybrid],
            ['MNO5P67', '3FAFP31391R004655', 'Volkswagen', 'T-Cross', 'Highline', 119900, 'Azul', 21000, Transmission::Automatic, FuelType::Diesel],
            ['PQR6S78', 'KMHCG45C2YU004756', 'Renault', 'Kwid', 'E-Tech', 149900, 'Vermelho', 8000, Transmission::Automatic, FuelType::Electric],
            ['STU7V89', '2T1BR32E54C004857', 'Nissan', 'Kicks', 'Sense', 109900, 'Cinza', 45000, Transmission::Manual, FuelType::Flex],
            ['VWX8Y90', '1N4AL11D75C004958', 'Hyundai', 'HB20', 'Comfort', 72900, 'Prata', 52000, Transmission::Manual, FuelType::Gasoline],
            ['YZA9B01', '4T1BF28B33U005059', 'Jeep', 'Renegade', 'Sport', 99800, 'Verde', 38000, Transmission::Automatic, FuelType::Ethanol],
            ['BCD0E12', '5YJSA1E26HF005160', 'BYD', 'Dolphin', 'Plus', 159800, 'Branco', 12000, Transmission::Automatic, FuelType::Electric],
        ];
        foreach ($vehicles as $index => [$placa, $chassi, $marca, $modelo, $versao, $valor, $cor, $km, $cambio, $combustivel]) {
            $owner = $index % 2 === 0 ? $admin : $user;
            $vehicle = Vehicle::factory()->forOwner($owner)->create(compact('placa', 'chassi', 'marca', 'modelo', 'versao', 'cor', 'km', 'cambio', 'combustivel') + ['valor_venda' => $valor]);
            $path = "vehicles/{$vehicle->id}/placeholder.png";
            Storage::disk('public')->put($path, file_get_contents(database_path('seeders/assets/vehicle-placeholder.png')));
            VehicleImage::create(['vehicle_id' => $vehicle->id, 'path' => $path, 'is_cover' => true]);
            if ($index < 3) {
                $second = "vehicles/{$vehicle->id}/placeholder-2.png";
                Storage::disk('public')->copy($path, $second);
                VehicleImage::create(['vehicle_id' => $vehicle->id, 'path' => $second]);
            }
        }
    }
}
