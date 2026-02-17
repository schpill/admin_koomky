<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\RecurringInvoiceProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecurringInvoiceGeneratorService
{
    public function __construct(protected InvoiceCalculationService $calculationService) {}

    public function generate(RecurringInvoiceProfile $profile): Invoice
    {
        /** @var array<int, array<string, mixed>> $lineItems */
        $lineItems = collect($profile->line_items)
            ->map(function (array $lineItem) use ($profile): array {
                return [
                    'description' => (string) ($lineItem['description'] ?? ''),
                    'quantity' => (float) ($lineItem['quantity'] ?? 1),
                    'unit_price' => (float) ($lineItem['unit_price'] ?? 0),
                    'vat_rate' => (float) ($lineItem['vat_rate'] ?? ($profile->tax_rate ?? 0)),
                ];
            })
            ->values()
            ->all();

        $discountPercent = $profile->discount_percent !== null ? (float) $profile->discount_percent : null;
        $calculation = $this->calculationService->calculate(
            $lineItems,
            $discountPercent !== null ? 'percentage' : null,
            $discountPercent,
        );

        $issueDate = Carbon::parse($profile->next_due_date)->startOfDay();
        $dueDate = $issueDate->copy()->addDays($profile->payment_terms_days)->toDateString();

        return DB::transaction(function () use ($profile, $lineItems, $calculation, $issueDate, $dueDate): Invoice {
            $invoice = Invoice::query()->create([
                'user_id' => $profile->user_id,
                'client_id' => $profile->client_id,
                'project_id' => null,
                'recurring_invoice_profile_id' => $profile->id,
                'number' => ReferenceGenerator::generate('invoices', 'FAC'),
                'status' => 'draft',
                'issue_date' => $issueDate->toDateString(),
                'due_date' => $dueDate,
                'subtotal' => $calculation['subtotal'],
                'tax_amount' => $calculation['tax_amount'],
                'discount_type' => $profile->discount_percent !== null ? 'percentage' : null,
                'discount_value' => $profile->discount_percent,
                'discount_amount' => $calculation['discount_amount'],
                'total' => $calculation['total'],
                'currency' => $profile->currency,
                'notes' => $profile->notes,
                'payment_terms' => $profile->payment_terms_days.' days',
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

            $nextDueDate = $this->computeNextDueDate($profile);
            $occurrencesGenerated = $profile->occurrences_generated + 1;

            $profile->update([
                'last_generated_at' => now(),
                'occurrences_generated' => $occurrencesGenerated,
                'next_due_date' => $nextDueDate,
                'status' => $this->shouldComplete($profile, $nextDueDate, $occurrencesGenerated) ? 'completed' : $profile->status,
            ]);

            return $invoice->load(['lineItems', 'client']);
        });
    }

    public function computeNextDueDate(RecurringInvoiceProfile $profile): Carbon
    {
        $currentDueDate = Carbon::parse($profile->next_due_date);

        $monthsToAdd = match ($profile->frequency) {
            'monthly' => 1,
            'quarterly' => 3,
            'semiannual' => 6,
            'annual' => 12,
            default => null,
        };

        if ($monthsToAdd !== null) {
            $candidate = $currentDueDate->copy()->addMonthsNoOverflow($monthsToAdd);
            $requestedDay = (int) ($profile->day_of_month ?? $currentDueDate->day);
            $targetDay = min(max(1, $requestedDay), $candidate->daysInMonth);

            return $candidate->setDay($targetDay);
        }

        return match ($profile->frequency) {
            'weekly' => $currentDueDate->copy()->addWeek(),
            'biweekly' => $currentDueDate->copy()->addWeeks(2),
            default => $currentDueDate->copy()->addMonthNoOverflow(),
        };
    }

    private function shouldComplete(RecurringInvoiceProfile $profile, Carbon $nextDueDate, int $occurrencesGenerated): bool
    {
        if ($profile->max_occurrences !== null && $occurrencesGenerated >= $profile->max_occurrences) {
            return true;
        }

        if ($profile->end_date !== null && $nextDueDate->greaterThan(Carbon::parse($profile->end_date))) {
            return true;
        }

        return false;
    }
}
