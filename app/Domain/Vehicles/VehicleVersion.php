<?php

namespace App\Domain\Vehicles;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class VehicleVersion
{
    public function etag(Vehicle $vehicle): string
    {
        return $this->etagFor($vehicle->getKey(), $vehicle->lock_version);
    }

    public function etagFor(int $vehicleId, int $lockVersion): string
    {
        return \sprintf('"vehicle-%d-v%d"', $vehicleId, $lockVersion);
    }

    public function expectedVersion(Request $request, Vehicle $vehicle): int
    {
        $ifMatch = \trim((string) $request->header('If-Match'));

        if ($ifMatch === '') {
            throw new HttpException(428, 'The If-Match header is required.');
        }

        $pattern = '/^"vehicle-'.\preg_quote((string) $vehicle->getKey(), '/').'-v([1-9][0-9]*)"$/D';

        if (\preg_match($pattern, $ifMatch, $matches) !== 1) {
            throw new HttpException(412, 'The Vehicle version is no longer current.');
        }

        return (int) $matches[1];
    }

    public function assertCurrent(Vehicle $vehicle, int $expectedVersion): void
    {
        if ($vehicle->lock_version !== $expectedVersion) {
            throw new HttpException(412, 'The Vehicle version is no longer current.');
        }
    }
}
