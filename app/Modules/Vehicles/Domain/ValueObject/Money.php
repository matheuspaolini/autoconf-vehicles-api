<?php

namespace App\Modules\Vehicles\Domain\ValueObject;

use App\Modules\Vehicles\Domain\Exception\InvalidVehicleValue;

final readonly class Money
{
    private const MAXIMUM_MINOR_UNITS = 999999999999999;

    public function __construct(public int $minorUnits)
    {
        if ($minorUnits < 1 || $minorUnits > self::MAXIMUM_MINOR_UNITS) {
            throw InvalidVehicleValue::forField('valor_venda');
        }
    }

    public static function fromDecimal(string $value): self
    {
        if (\preg_match('/^(\d{1,13})(?:\.(\d{1,2}))?$/D', $value, $parts) !== 1) {
            throw InvalidVehicleValue::forField('valor_venda');
        }

        $fraction = \str_pad($parts[2] ?? '', 2, '0');

        return new self(((int) $parts[1] * 100) + (int) $fraction);
    }

    public function decimal(): string
    {
        return \sprintf('%d.%02d', \intdiv($this->minorUnits, 100), $this->minorUnits % 100);
    }
}
