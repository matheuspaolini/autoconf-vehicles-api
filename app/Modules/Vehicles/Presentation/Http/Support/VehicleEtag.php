<?php

namespace App\Modules\Vehicles\Presentation\Http\Support;

use App\Modules\Vehicles\Application\Data\EtagParseData;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class VehicleEtag
{
    private const FORMAT = '"vehicle-%d-v%d"';

    public function expectedVersion(Request $request, int $vehicleId): int
    {
        $parsed = $this->parse($request, $vehicleId);

        if ($parsed->missing) {
            throw new HttpException(428, 'The If-Match header is required.');
        }

        if ($parsed->version === null) {
            throw new HttpException(412, 'The Vehicle version is no longer current.');
        }

        return $parsed->version;
    }

    public function parse(Request $request, int $vehicleId): EtagParseData
    {
        $value = \trim((string) $request->header('If-Match'));

        if ($value === '') {
            return new EtagParseData(null, true);
        }

        $pattern = '/^"vehicle-'.\preg_quote((string) $vehicleId, '/').'-v([1-9][0-9]*)"$/D';

        return new EtagParseData(
            version: \preg_match($pattern, $value, $matches) === 1 ? (int) $matches[1] : null,
            missing: false,
        );
    }

    public function format(int $vehicleId, int $version): string
    {
        return \sprintf(self::FORMAT, $vehicleId, $version);
    }
}
