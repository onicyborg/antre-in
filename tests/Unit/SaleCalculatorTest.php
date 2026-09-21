<?php

namespace Tests\Unit;

use App\Services\SaleCalculator;
use PHPUnit\Framework\TestCase;

class SaleCalculatorTest extends TestCase
{
    public function test_calculates_nominal_discount_tax_and_change(): void
    {
        $result = (new SaleCalculator())->calculate([['quantity'=>2,'unit_price'=>12500],['quantity'=>1,'unit_price'=>10000]], 'nominal', 5000, 11, 50000, 'cash');
        $this->assertSame(35000, $result['subtotal']); $this->assertSame(5000, $result['discount_amount']); $this->assertSame(3300, $result['tax_amount']); $this->assertSame(33300, $result['total']); $this->assertSame(16700, $result['change_amount']);
    }

    public function test_percent_discount_is_floored_and_non_cash_has_no_change(): void
    {
        $calculator = new SaleCalculator();
        $this->assertSame(333, $calculator->discountAmount(3333, 'percent', 10));
        $this->assertSame(0, $calculator->changeAmount(10000, 10000, 'qris'));
        $this->assertSame(10000, $calculator->taxAmount(100000, 10));
    }

    public function test_nominal_discount_cannot_exceed_subtotal(): void
    {
        $this->assertSame(1000, (new SaleCalculator())->discountAmount(1000, 'nominal', 5000));
    }
}
