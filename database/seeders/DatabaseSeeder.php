<?php

namespace Database\Seeders;

use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Models\User;
use App\Models\Vehicle;
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
            ['CDE1F23', '9BFZF54A8D8051611', 'Ford', 'Ranger', 'Limited', 289900, 'Cinza', 17000, Transmission::Automatic, FuelType::Diesel],
            ['EFG2H34', '93Y5SRD64LJ051622', 'Renault', 'Duster', 'Iconic', 124900, 'Marrom', 32000, Transmission::Automatic, FuelType::Flex],
            ['FGH3I45', '9BGKS48U0KG051723', 'Chevrolet', 'Tracker', 'Premier', 139900, 'Prata', 27000, Transmission::Automatic, FuelType::Flex],
            ['HIJ4K56', '3VW2K7AJ5FM051824', 'Volkswagen', 'Nivus', 'Comfortline', 122900, 'Azul', 29000, Transmission::Automatic, FuelType::Flex],
            ['IJK5L67', '8AFAR23L6LJ051925', 'Ford', 'Maverick', 'Lariat', 238900, 'Vermelho', 19000, Transmission::Automatic, FuelType::Hybrid],
            ['KLM6N78', '93HFC6630PK052026', 'Honda', 'HR-V', 'EXL', 162900, 'Branco', 14000, Transmission::Automatic, FuelType::Flex],
            ['LMN7O89', '9BRK29BT6N1052127', 'Toyota', 'Hilux', 'SRX', 319900, 'Preto', 44000, Transmission::Automatic, FuelType::Diesel],
            ['NOP8P90', '94J2XCCM8NP052228', 'Jeep', 'Compass', 'Longitude', 176900, 'Cinza', 23000, Transmission::Automatic, FuelType::Flex],
            ['OPQ9Q01', '9BWAB45U7NT052329', 'Volkswagen', 'Polo', 'TSI', 96900, 'Prata', 36000, Transmission::Automatic, FuelType::Flex],
            ['QRS0R12', '93YJAMG54PJ052430', 'Renault', 'Oroch', 'Outsider', 128900, 'Laranja', 31000, Transmission::Manual, FuelType::Flex],
            ['RST1S23', '9BD363A27PY052531', 'Fiat', 'Fastback', 'Impetus', 151900, 'Azul', 16000, Transmission::Automatic, FuelType::Flex],
            ['TUV2T34', '95PZBN7G4PB052632', 'Caoa Chery', 'Tiggo 7', 'Pro Max Drive', 169900, 'Branco', 22000, Transmission::Automatic, FuelType::Gasoline],
            ['UVW3U45', '93HGD1740NY052733', 'Honda', 'City', 'Touring', 129900, 'Cinza', 25000, Transmission::Automatic, FuelType::Flex],
            ['WXY4V56', '9C6RG5020N0052834', 'Yamaha', 'MT-07', 'ABS', 47900, 'Preto', 9000, Transmission::Manual, FuelType::Gasoline],
            ['XYZ5W67', '9CDCF47AJPM052935', 'Suzuki', 'Jimny', 'Sierra', 189900, 'Verde', 13000, Transmission::Automatic, FuelType::Gasoline],
            ['ZAB6X78', '93Y4SRD64RJ053036', 'Renault', 'Kardian', 'Premiere Edition', 139900, 'Cinza', 6000, Transmission::Automatic, FuelType::Flex],
            ['ACE7Y89', '9BWDL45U4RT053137', 'Volkswagen', 'Taos', 'Highline', 199900, 'Branco', 11000, Transmission::Automatic, FuelType::Flex],
            ['BDF8Z90', '9BG148FK0RG053238', 'Chevrolet', 'S10', 'LTZ', 274900, 'Prata', 48000, Transmission::Automatic, FuelType::Diesel],
            ['CEG9A01', '9BFZH55L3R8053339', 'Ford', 'Territory', 'Titanium', 214900, 'Azul', 15000, Transmission::Automatic, FuelType::Gasoline],
            ['DFH0B12', '93HFC6640RK053440', 'Honda', 'CR-V', 'Advanced Hybrid', 352900, 'Preto', 7000, Transmission::Automatic, FuelType::Hybrid],
        ];
        foreach ($vehicles as $index => [$placa, $chassi, $marca, $modelo, $versao, $valor, $cor, $km, $cambio, $combustivel]) {
            $owner = $index % 2 === 0 ? $admin : $user;
            $vehicle = Vehicle::factory()->forOwner($owner)->create(compact('placa', 'chassi', 'marca', 'modelo', 'versao', 'cor', 'km', 'cambio', 'combustivel') + ['valor_venda' => $valor]);
            $path = "vehicles/{$vehicle->id}/placeholder.png";
            Storage::disk('public')->put($path, file_get_contents(database_path('seeders/assets/vehicle-placeholder.png')));
            // The source is already stored, so it is not an HTTP upload for the lifecycle.
            $image = $vehicle->images()->create(['path' => $path]);
            $image->forceFill(['is_cover' => $vehicle->images()->count() === 1])->save();
            if ($index < 3) {
                $second = "vehicles/{$vehicle->id}/placeholder-2.png";
                Storage::disk('public')->copy($path, $second);
                $vehicle->images()->create(['path' => $second]);
            }
        }
    }
}
