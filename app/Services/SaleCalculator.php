<?php

namespace App\Services;

use App\Enums\PaymentMethod;

final class SaleCalculator
{
    public function subtotal(array $items): int
    {
        return array_sum(array_map(static fn (array $item): int => (int) $item['quantity'] * (int) $item['unit_price'], $items));
    }

    public function discountAmount(int $subtotal, ?string $type, int $value): int
    {
        if ($value <= 0 || $subtotal <= 0) return 0;
        return $type === 'percent' ? min($subtotal, (int) floor($subtotal * $value / 100)) : min($subtotal, $value);
    }

    public function taxAmount(int $taxable, float|int $percent): int
    {
        return (int) round($taxable * (float) $percent / 100, 0, PHP_ROUND_HALF_UP);
    }

    public function changeAmount(int $total, int $paid, string|PaymentMethod $method): int
    {
        return ($method instanceof PaymentMethod ? $method->value : $method) === PaymentMethod::Cash->value ? max(0, $paid - $total) : 0;
    }

    public function calculate(array $items, ?string $discountType = null, int $discountValue = 0, float|int $taxPercent = 0, int $paidAmount = 0, string|PaymentMethod $paymentMethod = PaymentMethod::Cash): array
    {
        $subtotal = $this->subtotal($items);
        $discount = $this->discountAmount($subtotal, $discountType, $discountValue);
        $taxable = $subtotal - $discount;
        $tax = $this->taxAmount($taxable, $taxPercent);
        $total = $taxable + $tax;
        return ['subtotal'=>$subtotal,'discount_amount'=>$discount,'taxable'=>$taxable,'tax_amount'=>$tax,'total'=>$total,'paid_amount'=>$paidAmount,'change_amount'=>$this->changeAmount($total, $paidAmount, $paymentMethod)];
    }

    public function discount(int $subtotal, ?string $type, int $value): int { return $this->discountAmount($subtotal, $type, $value); }
    public function tax(int $taxable, float|int $percent): int { return $this->taxAmount($taxable, $percent); }
    public function change(int $total, int $paid, string|PaymentMethod $method): int { return $this->changeAmount($total, $paid, $method); }
    public function calculateDiscount(int $subtotal, ?string $type, int $value): int { return $this->discountAmount($subtotal, $type, $value); }
    public function calculateTax(int $taxable, float|int $percent): int { return $this->taxAmount($taxable, $percent); }
}
