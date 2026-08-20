<?php

declare(strict_types=1);

namespace App\Tests\Shared;

use App\Shared\Decimal\Decimal;
use PHPUnit\Framework\TestCase;

final class DecimalTest extends TestCase
{
    public function testConvertsDecimalToMinorUnitsWithoutFloat(): void
    {
        self::assertSame('9000000', Decimal::toMinorUnits('9.00', 6));
        self::assertSame('1234567', Decimal::toMinorUnits('1.234567', 6));
        self::assertSame('1', Decimal::toMinorUnits('0.000001', 6));
    }

    public function testComparesLargeIntegerStrings(): void
    {
        self::assertSame(1, Decimal::compareIntegerStrings('100000000000000000000', '99999999999999999999'));
        self::assertSame(0, Decimal::compareIntegerStrings('0009', '9'));
        self::assertSame(-1, Decimal::compareIntegerStrings('8', '9'));
    }
}
