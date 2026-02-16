<?php

namespace App\Services;

class InvoiceCalculationService
{
    /**
     * @param  array<int, array<string, mixed>>  $lineItems
     * @return array<string, mixed>
     */
    public function calculate(array $lineItems, ?string $discountType = null, float|int|null $discountValue = null): array
    {
        $normalizedItems = [];
        $subtotal = 0.0;

        foreach ($lineItems as $lineItem) {
            $quantity = round((float) ($lineItem['quantity'] ?? 0), 2);
            $unitPrice = round((float) ($lineItem['unit_price'] ?? 0), 2);
            $vatRate = (float) ($lineItem['vat_rate'] ?? 0);
            $lineTotal = round($quantity * $unitPrice, 2);

            $subtotal += $lineTotal;

            $normalizedItems[] = [
                'vat_rate' => $vatRate,
                'line_total' => $lineTotal,
            ];
        }

        $subtotal = round($subtotal, 2);

        $discountAmount = $this->resolveDiscountAmount($subtotal, $discountType, $discountValue);
        $taxableSubtotal = round(max(0, $subtotal - $discountAmount), 2);

        $vatBreakdown = [];
        $allocatedDiscount = 0.0;
        $lastIndex = count($normalizedItems) - 1;

        foreach ($normalizedItems as $index => $item) {
            $rate = (float) $item['vat_rate'];
            $lineTotal = (float) $item['line_total'];

            if ($index === $lastIndex) {
                $lineDiscount = round($discountAmount - $allocatedDiscount, 2);
            } else {
                $lineDiscount = $subtotal > 0
                    ? round($discountAmount * ($lineTotal / $subtotal), 2)
                    : 0.0;
                $allocatedDiscount += $lineDiscount;
            }

            $taxableLine = round(max(0, $lineTotal - $lineDiscount), 2);
            $lineVat = round($taxableLine * ($rate / 100), 2);

            $rateKey = $this->rateKey($rate);
            $vatBreakdown[$rateKey] = round(((float) ($vatBreakdown[$rateKey] ?? 0.0)) + $lineVat, 2);
        }

        $taxAmount = round(array_sum($vatBreakdown), 2);
        $total = round($taxableSubtotal + $taxAmount, 2);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'taxable_subtotal' => $taxableSubtotal,
            'vat_breakdown' => $vatBreakdown,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ];
    }

    private function resolveDiscountAmount(float $subtotal, ?string $discountType, float|int|null $discountValue): float
    {
        $value = max(0, (float) ($discountValue ?? 0));

        if ($subtotal <= 0 || $discountType === null || $discountType === '') {
            return 0.0;
        }

        if ($discountType === 'percentage') {
            return round(min($subtotal, ($subtotal * min(100, $value)) / 100), 2);
        }

        if ($discountType === 'fixed') {
            return round(min($subtotal, $value), 2);
        }

        return 0.0;
    }

    private function rateKey(float $rate): string
    {
        return rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.');
    }
}
