<?php

namespace Tests\Unit\Modules\Vehicles\Domain;

use App\Modules\Vehicles\Domain\Exception\InvalidVehicleValue;
use App\Modules\Vehicles\Domain\ValueObject\Chassis;
use App\Modules\Vehicles\Domain\ValueObject\Money;
use App\Modules\Vehicles\Domain\ValueObject\Plate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class VehicleValueObjectsTest extends TestCase
{
    public function test_plate_and_chassis_are_normalized_once(): void
    {
        self::assertSame('ABC1D23', new Plate(' abc1d23 ')->value);
        self::assertSame('9BWZZZ377VT004251', new Chassis(' 9bwzzz377vt004251 ')->value);
    }

    #[DataProvider('invalidMoney')]
    public function test_money_rejects_values_outside_the_database_contract(string $value): void
    {
        $this->expectException(InvalidVehicleValue::class);

        Money::fromDecimal($value);
    }

    /** @return list<array{string}> */
    public static function invalidMoney(): array
    {
        return [['0'], ['-1.00'], ['1.001'], ['10000000000000.00']];
    }

    public function test_money_round_trips_as_a_two_decimal_string(): void
    {
        self::assertSame('125900.00', Money::fromDecimal('125900')->decimal());
        self::assertSame('10.50', Money::fromDecimal('10.5')->decimal());
    }
}
