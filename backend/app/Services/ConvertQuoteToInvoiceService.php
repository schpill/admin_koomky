<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConvertQuoteToInvoiceService
{
    public function __construct(
        protected InvoiceCalculationService $calculationService,
        protected CurrencyConversionService $currencyConversionService
    ) {}

    public function convert(Quote $quote): Invoice
    {
        $quote->loadMissing('lineItems', 'user');

        $lineItems = $quote->lineItems
            ->sortBy('sort_order')
            ->map(function ($lineItem): array {
                return [
                    'description' => $lineItem->description,
                    'quantity' => (float) $lineItem->quantity,
                    'unit_price' => (float) $lineItem->unit_price,
                    'vat_rate' => (float) $lineItem->vat_rate,
                ];
            })
            ->values()
            ->all();

        /** @var Invoice $invoice */
        $invoice = DB::transaction(function () use ($quote, $lineItems): Invoice {
            $user = $quote->user;
            if (! $user) {
                throw new RuntimeException('Quote user not found');
            }

            $paymentTermsDays = (int) $user->payment_terms_days;

            $calculation = $this->calculationService->calculate(
                $lineItems,
                $quote->discount_type,
                $quote->discount_value,
            );

            $issueDate = now();
            $currency = strtoupper((string) $quote->currency);
            $baseCurrency = strtoupper((string) ($user->base_currency ?? 'EUR'));
            $exchangeRate = $this->currencyConversionService->rateFor(
                $currency,
                $baseCurrency,
                $issueDate
            );
            $baseCurrencyTotal = $this->currencyConversionService->convert(
                (float) $calculation['total'],
                $currency,
                $baseCurrency,
                $issueDate
            );

            $invoice = Invoice::query()->create([
                'user_id' => $quote->user_id,
                'client_id' => $quote->client_id,
                'project_id' => $quote->project_id,
                'number' => ReferenceGenerator::generate('invoices', 'FAC'),
                'status' => 'draft',
                'issue_date' => $issueDate->toDateString(),
                'due_date' => now()->addDays($paymentTermsDays)->toDateString(),
                'subtotal' => $calculation['subtotal'],
                'tax_amount' => $calculation['tax_amount'],
                'discount_type' => $quote->discount_type,
                'discount_value' => $quote->discount_value,
                'discount_amount' => $calculation['discount_amount'],
                'total' => $calculation['total'],
                'currency' => $currency,
                'base_currency' => $baseCurrency,
                'exchange_rate' => $exchangeRate,
                'base_currency_total' => $baseCurrencyTotal,
                'notes' => $quote->notes,
                'payment_terms' => $paymentTermsDays.' days',
            ]);

            foreach ($lineItems as $index => $lineItem) {
                $invoice->lineItems()->create([
                    'description' => $lineItem['description'],
                    'quantity' => $lineItem['quantity'],
                    'unit_price' => $lineItem['unit_price'],
                    'vat_rate' => $lineItem['vat_rate'],
                    'sort_order' => $index,
                ]);
            }

            $quote->update([
                'converted_invoice_id' => $invoice->id,
                'status' => 'accepted',
                'accepted_at' => $quote->accepted_at ?? now(),
            ]);

            return $invoice;
        });

        return $invoice;
    }
}
