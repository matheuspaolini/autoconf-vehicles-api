<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Vehicles\Domain\Enum\FuelType;
use App\Modules\Vehicles\Domain\Enum\Transmission;
use App\Modules\Vehicles\Infrastructure\Persistence\Eloquent\VehicleRecord as Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class VehicleSeeder extends Seeder
{
    private const COMMONS_API = 'https://commons.wikimedia.org/w/api.php';

    private const IMAGES_PER_VEHICLE = 4;

    private const IMAGE_CANDIDATE_LIMIT = 50;

    /** @var array<string, string> */
    private array $downloadedImages = [];

    /** @var array<string, true> */
    private array $usedSourceUrls = [];

    /** @var array<string, true> */
    private array $usedContentHashes = [];

    private bool $commonsAvailable = true;

    public function run(User $admin, User $user): void
    {
        foreach ($this->vehicles() as $index => $data) {
            $owner = $index % 2 === 0 ? $admin : $user;
            $vehicle = Vehicle::factory()->forOwner($owner)->create($data);

            $this->seedGallery($vehicle);
        }
    }

    /** @return list<array<string, mixed>> */
    private function vehicles(): array
    {
        return [
            $this->vehicle('ABC1D23', '9BWZZZ377VT004251', 'Chevrolet', 'Onix', 'LT', 78900, 'Prata', 25000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('DEF2G34', '1HGCM82633A004352', 'Fiat', 'Pulse', 'Drive', 94500, 'Branco', 18000, Transmission::Manual, FuelType::Gasoline),
            $this->vehicle('GHI3J45', 'JH4KA8270MC004453', 'Honda', 'Civic', 'Touring', 158900, 'Cinza', 41000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('JKL4M56', 'WVWZZZ1JZXW004554', 'Toyota', 'Corolla', 'GLi', 132500, 'Preto', 35000, Transmission::Automatic, FuelType::Hybrid),
            $this->vehicle('MNO5P67', '3FAFP31391R004655', 'Volkswagen', 'T-Cross', 'Highline', 119900, 'Azul', 21000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('PQR6S78', 'KMHCG45C2YU004756', 'Renault', 'Kwid E-Tech', 'Intense', 149900, 'Vermelho', 8000, Transmission::Automatic, FuelType::Electric),
            $this->vehicle('STU7V89', '2T1BR32E54C004857', 'Nissan', 'Kicks', 'Sense', 109900, 'Cinza', 45000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('VWX8Y90', '1N4AL11D75C004958', 'Hyundai', 'HB20', 'Comfort', 72900, 'Prata', 52000, Transmission::Manual, FuelType::Gasoline),
            $this->vehicle('YZA9B01', '4T1BF28B33U005059', 'Jeep', 'Renegade', 'Sport', 99800, 'Verde', 38000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('BCD0E12', '5YJSA1E26HF005160', 'BYD', 'Dolphin', 'Plus', 159800, 'Branco', 12000, Transmission::Automatic, FuelType::Electric),
            $this->vehicle('CDE1F23', '9BFZF54A8D8051611', 'Ford', 'Ranger', 'Limited', 289900, 'Cinza', 17000, Transmission::Automatic, FuelType::Diesel),
            $this->vehicle('EFG2H34', '93Y5SRD64LJ051622', 'Renault', 'Duster', 'Iconic', 124900, 'Marrom', 32000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('FGH3I45', '9BGKS48U0KG051723', 'Chevrolet', 'Tracker', 'Premier', 139900, 'Prata', 27000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('HIJ4K56', '3VW2K7AJ5FM051824', 'Volkswagen', 'Nivus', 'Comfortline', 122900, 'Azul', 29000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('IJK5L67', '8AFAR23L6LJ051925', 'Ford', 'Maverick', 'Lariat', 238900, 'Vermelho', 19000, Transmission::Automatic, FuelType::Hybrid),
            $this->vehicle('KLM6N78', '93HFC6630PK052026', 'Honda', 'HR-V', 'EXL', 162900, 'Branco', 14000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('LMN7O89', '9BRK29BT6N1052127', 'Toyota', 'Hilux', 'SRX', 319900, 'Preto', 44000, Transmission::Automatic, FuelType::Diesel),
            $this->vehicle('NOP8P90', '94J2XCCM8NP052228', 'Jeep', 'Compass', 'Longitude', 176900, 'Cinza', 23000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('OPQ9Q01', '9BWAB45U7NT052329', 'Volkswagen', 'Polo', 'TSI', 96900, 'Prata', 36000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('QRS0R12', '93YJAMG54PJ052430', 'Renault', 'Oroch', 'Outsider', 128900, 'Laranja', 31000, Transmission::Manual, FuelType::Flex),
            $this->vehicle('RST1S23', '9BD363A27PY052531', 'Fiat', 'Fastback', 'Impetus', 151900, 'Azul', 16000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('TUV2T34', '95PZBN7G4PB052632', 'Caoa Chery', 'Tiggo 7', 'Pro Max Drive', 169900, 'Branco', 22000, Transmission::Automatic, FuelType::Gasoline),
            $this->vehicle('UVW3U45', '93HGD1740NY052733', 'Honda', 'City', 'Touring', 129900, 'Cinza', 25000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('WXY4V56', '9C6RG5020N0052834', 'Yamaha', 'MT-07', 'ABS', 47900, 'Preto', 9000, Transmission::Manual, FuelType::Gasoline),
            $this->vehicle('XYZ5W67', '9CDCF47AJPM052935', 'Suzuki', 'Jimny Sierra', '4Style', 189900, 'Verde', 13000, Transmission::Automatic, FuelType::Gasoline),
            $this->vehicle('ZAB6X78', '93Y4SRD64RJ053036', 'Renault', 'Kardian', 'Premiere Edition', 139900, 'Cinza', 6000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('ACE7Y89', '9BWDL45U4RT053137', 'Volkswagen', 'Taos', 'Highline', 199900, 'Branco', 11000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('BDF8Z90', '9BG148FK0RG053238', 'Chevrolet', 'S10', 'LTZ', 274900, 'Prata', 48000, Transmission::Automatic, FuelType::Diesel),
            $this->vehicle('CEG9A01', '9BFZH55L3R8053339', 'Ford', 'Territory', 'Titanium', 214900, 'Azul', 15000, Transmission::Automatic, FuelType::Gasoline),
            $this->vehicle('DFH0B12', '93HFC6640RK053440', 'Honda', 'CR-V', 'Advanced Hybrid', 352900, 'Preto', 7000, Transmission::Automatic, FuelType::Hybrid),
            $this->vehicle('EJK1C23', '9BGEB69H0RG053541', 'Chevrolet', 'Montana', 'Premier', 154900, 'Vermelho', 10500, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('FLM2D34', '9BD281A3MR7053642', 'Fiat', 'Strada', 'Volcano', 128900, 'Cinza', 28000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('GNP3E45', '9BRB33BE7R2053743', 'Toyota', 'Corolla Cross', 'XRX Hybrid', 219900, 'Branco', 12500, Transmission::Automatic, FuelType::Hybrid),
            $this->vehicle('HQR4F56', '9BWCK6BF5RP053844', 'Volkswagen', 'Amarok', 'Extreme', 309900, 'Azul', 33000, Transmission::Automatic, FuelType::Diesel),
            $this->vehicle('IST5G67', '98861110XPK053945', 'Jeep', 'Commander', 'Overland', 249900, 'Preto', 18500, Transmission::Automatic, FuelType::Diesel),
            $this->vehicle('JVU6H78', '95PED81D0RB054046', 'Hyundai', 'Creta', 'Ultimate', 179900, 'Prata', 9000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('KXZ7I89', '94DBCAN17RB054147', 'Nissan', 'Sentra', 'Exclusive', 174900, 'Cinza', 11500, Transmission::Automatic, FuelType::Gasoline),
            $this->vehicle('LAY8J90', '9C2RH1130RR054248', 'Honda', 'CB 500X', 'ABS', 52900, 'Vermelho', 6500, Transmission::Manual, FuelType::Gasoline),
            $this->vehicle('MBZ9K01', '98RDFY41XRA054349', 'BYD', 'Song Plus', 'DM-i', 239800, 'Branco', 5000, Transmission::Automatic, FuelType::Hybrid),
            $this->vehicle('NCD0L12', '9BGAH69S0RB054450', 'Chevrolet', 'Equinox', 'Premier', 229900, 'Preto', 14500, Transmission::Automatic, FuelType::Gasoline),
            $this->vehicle('ODE1M23', 'WVWZZZ1KZRW054551', 'Volkswagen', 'Golf', 'GTI', 289900, 'Branco', 8500, Transmission::Automatic, FuelType::Gasoline),
            $this->vehicle('PEF2N34', '3VWDP7AJ5RM054652', 'Volkswagen', 'Jetta', 'GLI', 239900, 'Cinza', 12000, Transmission::Automatic, FuelType::Gasoline),
            $this->vehicle('QFG3O45', '1G1FH1R79R0054753', 'Chevrolet', 'Camaro', 'SS', 529900, 'Amarelo', 6000, Transmission::Automatic, FuelType::Gasoline),
            $this->vehicle('RGH4P56', '1G1BE5SMXR7054854', 'Chevrolet', 'Cruze', 'Premier', 149900, 'Preto', 31000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('SHI5Q67', '9BD358A1NRK054955', 'Fiat', 'Argo', 'Trekking', 96900, 'Vermelho', 19000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('TIJ6R78', '3C3CFFBR1RT055056', 'Fiat', '500', 'Cabrio', 189900, 'Branco', 14000, Transmission::Automatic, FuelType::Gasoline),
            $this->vehicle('UKL7S89', '9BRK29BT7R1055157', 'Toyota', 'Yaris', 'XLS', 119900, 'Prata', 22000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('VLM8T90', 'JTMRWRFV5RD055258', 'Toyota', 'RAV4', 'SX Hybrid', 349900, 'Azul', 17000, Transmission::Automatic, FuelType::Hybrid),
            $this->vehicle('WMN9U01', '93HGK5870RZ055359', 'Honda', 'Fit', 'EXL', 98900, 'Cinza', 39000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('XOP0V12', '1HGCV1F30RA055460', 'Honda', 'Accord', 'Touring Hybrid', 329900, 'Preto', 11000, Transmission::Automatic, FuelType::Hybrid),
            $this->vehicle('YPQ1W23', '1FA6P8TH6R5055561', 'Ford', 'Mustang', 'GT', 489900, 'Vermelho', 7000, Transmission::Automatic, FuelType::Gasoline),
            $this->vehicle('ZRS2X34', '1FMDE5BH2RLA55662', 'Ford', 'Bronco', 'Wildtrak', 279900, 'Laranja', 13000, Transmission::Automatic, FuelType::Gasoline),
            $this->vehicle('ATU3Y45', '94DJAAN15RJ055763', 'Nissan', 'Versa', 'Exclusive', 129900, 'Azul', 16000, Transmission::Automatic, FuelType::Flex),
            $this->vehicle('BUV4Z56', '1N4AZ1CP9RC055864', 'Nissan', 'Leaf', 'Tekna', 219900, 'Branco', 9000, Transmission::Automatic, FuelType::Electric),
            $this->vehicle('CVX5A67', '95PJN81DPRA055965', 'Hyundai', 'Tucson', 'Limited', 199900, 'Prata', 21000, Transmission::Automatic, FuelType::Gasoline),
            $this->vehicle('DWY6B78', 'KM8S3DAF6RU056066', 'Hyundai', 'Santa Fe', 'Calligraphy', 399900, 'Preto', 10000, Transmission::Automatic, FuelType::Hybrid),
            $this->vehicle('EXZ7C89', '1C4HJXDG8RW056167', 'Jeep', 'Wrangler', 'Sahara', 459900, 'Verde', 8000, Transmission::Automatic, FuelType::Gasoline),
            $this->vehicle('FBA8D90', '1C4RJFBG5RC056268', 'Jeep', 'Grand Cherokee', 'Limited', 569900, 'Cinza', 12000, Transmission::Automatic, FuelType::Gasoline),
            $this->vehicle('GCB9E01', 'WBA5R1C00RFP56369', 'BMW', '320i', 'M Sport', 349900, 'Azul', 15000, Transmission::Automatic, FuelType::Gasoline),
            $this->vehicle('HDC0F12', 'W1KWF8EB2RR056470', 'Mercedes-Benz', 'C-Class', 'C 300 AMG Line', 429900, 'Branco', 9500, Transmission::Automatic, FuelType::Gasoline),
        ];
    }

    /** @return array<string, mixed> */
    private function vehicle(string $placa, string $chassi, string $marca, string $modelo, string $versao, int $valor, string $cor, int $km, Transmission $cambio, FuelType $combustivel): array
    {
        return \compact('placa', 'chassi', 'marca', 'modelo', 'versao', 'cor', 'km', 'cambio', 'combustivel') + ['valor_venda' => $valor];
    }

    private function seedGallery(Vehicle $vehicle): void
    {
        $images = $this->commonsImages($vehicle->marca, $vehicle->modelo);
        $stored = 0;

        foreach ($images as $image) {
            $path = $this->storeUniqueImage($vehicle, $image, $stored + 1);

            if ($path === null) {
                continue;
            }

            $image = $vehicle->images()->create(['path' => $path]);

            if ($stored === 0) {
                $image->forceFill(['is_cover' => true])->save();
            }

            $stored++;

            if ($stored === self::IMAGES_PER_VEHICLE) {
                break;
            }
        }
    }

    /** @return list<array{download_url: string, source_url: string, mime: string}> */
    private function commonsImages(string $brand, string $model): array
    {
        if (! $this->commonsAvailable) {
            return [];
        }

        $query = "{$brand} {$model}";
        $requiredWords = \array_values(\array_filter(
            \preg_split('/[^a-z0-9]+/', \strtolower($model)) ?: [],
            static fn (string $word): bool => \strlen($word) > 1,
        ));

        try {
            $response = $this->http()->get(self::COMMONS_API, [
                'action' => 'query', 'format' => 'json', 'generator' => 'search',
                'gsrnamespace' => 6, 'gsrlimit' => self::IMAGE_CANDIDATE_LIMIT,
                'gsrsearch' => 'filetype:bitmap '.\sprintf('"%s"', $query),
                'prop' => 'imageinfo', 'iiprop' => 'url|mime', 'iiurlwidth' => 1280,
            ]);

            if (! $response->successful()) {
                $this->commonsAvailable = false;

                return [];
            }

            $pages = \array_values($response->json('query.pages', []));
            \usort($pages, static fn (array $left, array $right): int => ($left['index'] ?? 0) <=> ($right['index'] ?? 0));
            $images = [];

            foreach ($pages as $page) {
                $info = $page['imageinfo'][0] ?? null;
                $downloadUrl = $info['thumburl'] ?? $info['url'] ?? null;
                $sourceUrl = $info['url'] ?? null;
                $mime = $info['mime'] ?? null;
                $title = \strtolower($page['title'] ?? '');
                $matchesModel = $this->containsEveryWord($title, $requiredWords);

                if ($matchesModel && \is_string($downloadUrl) && \is_string($sourceUrl) && \in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                    $images[] = ['download_url' => $downloadUrl, 'source_url' => $sourceUrl, 'mime' => $mime];
                }
            }

            return $images;
        } catch (\Throwable) {
            $this->commonsAvailable = false;

            return [];
        }
    }

    /** @param array{download_url: string, source_url: string, mime: string} $image */
    private function storeUniqueImage(Vehicle $vehicle, array $image, int $position): ?string
    {
        if (\array_key_exists($image['source_url'], $this->usedSourceUrls)) {
            return null;
        }

        $contents = $this->download($image['download_url']);

        if ($contents === null) {
            return null;
        }

        $contentHash = \hash('sha256', $contents);

        if (\array_key_exists($contentHash, $this->usedContentHashes)) {
            return null;
        }

        $extension = match ($image['mime']) {
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => 'png',
        };
        $path = "vehicles/{$vehicle->id}/gallery-{$position}.{$extension}";

        Storage::disk('public')->put($path, $contents);
        $this->usedSourceUrls[$image['source_url']] = true;
        $this->usedContentHashes[$contentHash] = true;

        return $path;
    }

    private function download(string $url): ?string
    {
        if (\array_key_exists($url, $this->downloadedImages)) {
            return $this->downloadedImages[$url];
        }

        try {
            $response = $this->http()->get($url);

            return $response->successful()
                ? $this->downloadedImages[$url] = $response->body()
                : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** @param list<string> $words */
    private function containsEveryWord(string $value, array $words): bool
    {
        foreach ($words as $word) {
            if (! \str_contains($value, $word)) {
                return false;
            }
        }

        return true;
    }

    private function http(): PendingRequest
    {
        return Http::withUserAgent('Autoconf Vehicles local database seeder')
            ->connectTimeout(5)
            ->timeout(15)
            ->retry(2, 200, throw: false);
    }
}
