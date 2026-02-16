<?php

use App\Services\InvoiceCalculationService;
use PHPUnit\Framework\Assert;

test('invoice calculation computes subtotal vat discount and total', function () {
    $service = app(InvoiceCalculationService::class);

    $result = $service->calculate(
        [
            [
                'description' => 'Design',
                'quantity' => 2,
                'unit_price' => 100,
                'vat_rate' => 20,
            ],
            [
                'description' => 'Development',
                'quantity' => 5,
                'unit_price' => 80,
                'vat_rate' => 10,
            ],
        ],
        'percentage',
        10
    );

    expect($result['subtotal'])->toBe(600.0);
    expect($result['discount_amount'])->toBe(60.0);
    expect($result['taxable_subtotal'])->toBe(540.0);

    Assert::assertArrayHasKey('vat_breakdown', $result);
    Assert::assertArrayHasKey('20', $result['vat_breakdown']);
    Assert::assertArrayHasKey('10', $result['vat_breakdown']);

    expect($result['tax_amount'])->toBeGreaterThan(0);
    expect($result['total'])->toBeGreaterThan($result['taxable_subtotal']);
});

test('invoice calculation supports fixed discount and rounding', function () {
    $service = app(InvoiceCalculationService::class);

    $result = $service->calculate(
        [
            [
                'description' => 'Service A',
                'quantity' => 1,
                'unit_price' => 100.005,
                'vat_rate' => 20,
            ],
        ],
        'fixed',
        10
    );

    expect($result['subtotal'])->toBe(100.01);
    expect($result['discount_amount'])->toBe(10.0);
    expect($result['taxable_subtotal'])->toBe(90.01);
    expect($result['total'])->toBe(108.01);
});
