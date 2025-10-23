<?php

declare(strict_types=1);

namespace App\Test\Shared;

use App\Shared\Currency;
use App\Shared\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testCreateValidMoney(): void
    {
        $money = new Money(10.50, Currency::GBP);

        self::assertSame(10.50, $money->amount);
        self::assertSame(Currency::GBP, $money->currency);
    }

    public function testCannotCreateNegativeMoney(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Money amount cannot be negative');

        new Money(-5.00, Currency::GBP);
    }

    public function testAddMoneyWithSameCurrency(): void
    {
        $money1 = new Money(10.00, Currency::GBP);
        $money2 = new Money(5.50, Currency::GBP);

        $result = $money1->add($money2);

        self::assertSame(15.50, $result->amount);
        self::assertSame(Currency::GBP, $result->currency);
    }

    public function testCannotAddMoneyWithDifferentCurrencies(): void
    {
        $money1 = new Money(10.00, Currency::GBP);
        $money2 = new Money(5.00, Currency::USD);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot add money with different currencies');

        $money1->add($money2);
    }

    public function testMultiplyMoney(): void
    {
        $money = new Money(10.00, Currency::GBP);

        $result = $money->multiply(2.5);

        self::assertSame(25.00, $result->amount);
        self::assertSame(Currency::GBP, $result->currency);
    }

    public function testCannotMultiplyByNegativeValue(): void
    {
        $money = new Money(10.00, Currency::GBP);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Multiplier cannot be negative');

        $money->multiply(-2.0);
    }

    public function testToString(): void
    {
        $money = new Money(10.50, Currency::GBP);

        self::assertSame('GBP 10.50', (string)$money);
    }

    public function testToStringFormatsWithTwoDecimals(): void
    {
        $money = new Money(10.0, Currency::USD);

        self::assertSame('USD 10.00', (string)$money);
    }
}
