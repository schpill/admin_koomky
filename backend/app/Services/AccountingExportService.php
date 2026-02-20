<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Service for generating accounting software exports.
 *
 * Supports Pennylane, Sage, and generic CSV formats.
 */
class AccountingExportService
{
    /**
     * Column mappings for different software targets.
     */
    private const PENNYLANE_COLUMNS = [
        'date',
        'piece_ref',
        'account_code',
        'label',
        'debit',
        'credit',
        'currency',
        'client_ref',
    ];

    private const SAGE_COLUMNS = [
        'Date',
        'Référence',
        'N° Compte',
        'Libellé',
        'Débit',
        'Crédit',
        'Devise',
    ];

    private const GENERIC_COLUMNS = [
        'date',
        'reference',
        'account_code',
        'account_name',
        'description',
        'debit',
        'credit',
        'currency',
    ];

    /**
     * Generate export for the given format.
     *
     * @param  array<string, mixed>  $options
     * @return \Generator<int, string>
     */
    public function generate(User $user, string $format, array $options = []): \Generator
    {
        $dateFrom = Carbon::parse($options['date_from'] ?? now()->startOfYear());
        $dateTo = Carbon::parse($options['date_to'] ?? now()->endOfYear());

        $entries = $this->collectEntries($user, $dateFrom, $dateTo);

        // Yield header row based on format
        yield $this->buildHeaderRow($format);

        // Yield data rows
        foreach ($entries as $entry) {
            yield $this->formatRow($entry, $format);
        }
    }

    /**
     * Get the column headers for a given format.
     */
    public function getColumns(string $format): array
    {
        return match ($format) {
            'pennylane' => self::PENNYLANE_COLUMNS,
            'sage' => self::SAGE_COLUMNS,
            default => self::GENERIC_COLUMNS,
        };
    }

    /**
     * Collect all accounting entries.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function collectEntries(User $user, Carbon $dateFrom, Carbon $dateTo): Collection
    {
        $entries = collect();

        // Collect invoice entries
        $entries = $entries->merge($this->collectInvoiceEntries($user, $dateFrom, $dateTo));

        // Collect credit note entries
        $entries = $entries->merge($this->collectCreditNoteEntries($user, $dateFrom, $dateTo));

        // Collect payment entries
        $entries = $entries->merge($this->collectPaymentEntries($user, $dateFrom, $dateTo));

        // Collect expense entries
        $entries = $entries->merge($this->collectExpenseEntries($user, $dateFrom, $dateTo));

        // Sort by date
        return $entries->sortBy('date')->values();
    }

    /**
     * Collect invoice entries.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function collectInvoiceEntries(User $user, Carbon $dateFrom, Carbon $dateTo): Collection
    {
        $entries = collect();

        $invoices = Invoice::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'draft')
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->with(['client'])
            ->get();

        foreach ($invoices as $invoice) {
            $total = (float) ($invoice->base_currency_total ?? $invoice->total);
            $taxAmount = (float) $invoice->tax_amount;
            $revenue = $total - $taxAmount;

            // Client debit
            $entries->push([
                'date' => $invoice->issue_date->format('Y-m-d'),
                'piece_ref' => $invoice->number,
                'account_code' => '411000',
                'account_name' => 'Clients',
                'description' => 'Facture '.$invoice->number.' - '.($invoice->client?->name ?? 'Client'),
                'debit' => $total,
                'credit' => 0,
                'currency' => $invoice->currency,
                'client_ref' => $invoice->client?->id,
            ]);

            // Revenue credit
            $entries->push([
                'date' => $invoice->issue_date->format('Y-m-d'),
                'piece_ref' => $invoice->number,
                'account_code' => '706000',
                'account_name' => 'Prestations de services',
                'description' => 'Facture '.$invoice->number,
                'debit' => 0,
                'credit' => $revenue,
                'currency' => $invoice->currency,
                'client_ref' => null,
            ]);

            // VAT credit (if applicable)
            if ($taxAmount > 0) {
                $entries->push([
                    'date' => $invoice->issue_date->format('Y-m-d'),
                    'piece_ref' => $invoice->number,
                    'account_code' => '445710',
                    'account_name' => 'TVA collectée',
                    'description' => 'TVA Facture '.$invoice->number,
                    'debit' => 0,
                    'credit' => $taxAmount,
                    'currency' => $invoice->currency,
                    'client_ref' => null,
                ]);
            }
        }

        return $entries;
    }

    /**
     * Collect credit note entries.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function collectCreditNoteEntries(User $user, Carbon $dateFrom, Carbon $dateTo): Collection
    {
        $entries = collect();

        $creditNotes = CreditNote::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'draft')
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->with(['client'])
            ->get();

        foreach ($creditNotes as $creditNote) {
            $total = (float) ($creditNote->base_currency_total ?? $creditNote->total);
            $taxAmount = (float) $creditNote->tax_amount;
            $revenue = $total - $taxAmount;

            // Client credit (reverse)
            $entries->push([
                'date' => $creditNote->issue_date->format('Y-m-d'),
                'piece_ref' => $creditNote->number,
                'account_code' => '411000',
                'account_name' => 'Clients',
                'description' => 'Avoir '.$creditNote->number.' - '.($creditNote->client?->name ?? 'Client'),
                'debit' => 0,
                'credit' => $total,
                'currency' => $creditNote->currency,
                'client_ref' => $creditNote->client?->id,
            ]);

            // Revenue debit (reverse)
            $entries->push([
                'date' => $creditNote->issue_date->format('Y-m-d'),
                'piece_ref' => $creditNote->number,
                'account_code' => '706000',
                'account_name' => 'Prestations de services',
                'description' => 'Avoir '.$creditNote->number,
                'debit' => $revenue,
                'credit' => 0,
                'currency' => $creditNote->currency,
                'client_ref' => null,
            ]);

            // VAT debit (reverse, if applicable)
            if ($taxAmount > 0) {
                $entries->push([
                    'date' => $creditNote->issue_date->format('Y-m-d'),
                    'piece_ref' => $creditNote->number,
                    'account_code' => '445710',
                    'account_name' => 'TVA collectée',
                    'description' => 'TVA Avoir '.$creditNote->number,
                    'debit' => $taxAmount,
                    'credit' => 0,
                    'currency' => $creditNote->currency,
                    'client_ref' => null,
                ]);
            }
        }

        return $entries;
    }

    /**
     * Collect payment entries.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function collectPaymentEntries(User $user, Carbon $dateFrom, Carbon $dateTo): Collection
    {
        $entries = collect();

        $payments = Payment::query()
            ->whereHas('invoice', function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->with(['invoice.client'])
            ->get();

        foreach ($payments as $payment) {
            $amount = (float) $payment->amount;
            $invoice = $payment->invoice;

            // Bank debit
            $entries->push([
                'date' => $payment->payment_date->format('Y-m-d'),
                'piece_ref' => $payment->reference ?? 'PMT-'.$payment->id,
                'account_code' => '512000',
                'account_name' => 'Banque',
                'description' => 'Encaissement '.$invoice->number,
                'debit' => $amount,
                'credit' => 0,
                'currency' => $invoice->currency ?? 'EUR',
                'client_ref' => null,
            ]);

            // Client credit
            $entries->push([
                'date' => $payment->payment_date->format('Y-m-d'),
                'piece_ref' => $payment->reference ?? 'PMT-'.$payment->id,
                'account_code' => '411000',
                'account_name' => 'Clients',
                'description' => 'Encaissement '.$invoice->number.' - '.($invoice->client?->name ?? 'Client'),
                'debit' => 0,
                'credit' => $amount,
                'currency' => $invoice->currency ?? 'EUR',
                'client_ref' => $invoice->client?->id,
            ]);
        }

        return $entries;
    }

    /**
     * Collect expense entries.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function collectExpenseEntries(User $user, Carbon $dateFrom, Carbon $dateTo): Collection
    {
        $entries = collect();

        $expenses = Expense::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->with(['category'])
            ->get();

        foreach ($expenses as $expense) {
            $amount = (float) ($expense->base_currency_amount ?? $expense->amount);
            $taxAmount = (float) $expense->tax_amount;
            $chargeAmount = $amount - $taxAmount;

            // Charge debit
            $entries->push([
                'date' => $expense->date->format('Y-m-d'),
                'piece_ref' => $expense->reference ?? 'EXP-'.$expense->id,
                'account_code' => $expense->category?->account_code ?? '622600',
                'account_name' => $expense->category?->name ?? 'Charges diverses',
                'description' => $expense->description,
                'debit' => $chargeAmount,
                'credit' => 0,
                'currency' => $expense->currency,
                'client_ref' => null,
            ]);

            // VAT debit (if applicable)
            if ($taxAmount > 0) {
                $entries->push([
                    'date' => $expense->date->format('Y-m-d'),
                    'piece_ref' => $expense->reference ?? 'EXP-'.$expense->id,
                    'account_code' => '445660',
                    'account_name' => 'TVA déductible',
                    'description' => 'TVA '.$expense->description,
                    'debit' => $taxAmount,
                    'credit' => 0,
                    'currency' => $expense->currency,
                    'client_ref' => null,
                ]);
            }

            // Supplier/Bank credit
            $creditAccount = $expense->payment_method === 'bank_transfer' ? '512000' : '401000';
            $creditName = $expense->payment_method === 'bank_transfer' ? 'Banque' : 'Fournisseurs';

            $entries->push([
                'date' => $expense->date->format('Y-m-d'),
                'piece_ref' => $expense->reference ?? 'EXP-'.$expense->id,
                'account_code' => $creditAccount,
                'account_name' => $creditName,
                'description' => ($expense->vendor ?? 'Fournisseur').' - '.$expense->description,
                'debit' => 0,
                'credit' => $amount,
                'currency' => $expense->currency,
                'client_ref' => null,
            ]);
        }

        return $entries;
    }

    /**
     * Build header row for the given format.
     */
    private function buildHeaderRow(string $format): string
    {
        $columns = $this->getColumns($format);

        return implode(';', $columns);
    }

    /**
     * Format a row for the given format.
     *
     * @param  array<string, mixed>  $entry
     */
    private function formatRow(array $entry, string $format): string
    {
        return match ($format) {
            'pennylane' => $this->formatPennylaneRow($entry),
            'sage' => $this->formatSageRow($entry),
            default => $this->formatGenericRow($entry),
        };
    }

    /**
     * Format a row for Pennylane.
     *
     * @param  array<string, mixed>  $entry
     */
    private function formatPennylaneRow(array $entry): string
    {
        return implode(';', [
            $entry['date'],
            $entry['piece_ref'],
            $entry['account_code'],
            $entry['description'],
            $this->formatAmount((float) $entry['debit']),
            $this->formatAmount((float) $entry['credit']),
            $entry['currency'],
            $entry['client_ref'] ?? '',
        ]);
    }

    /**
     * Format a row for Sage.
     *
     * @param  array<string, mixed>  $entry
     */
    private function formatSageRow(array $entry): string
    {
        return implode(';', [
            $entry['date'],
            $entry['piece_ref'],
            $entry['account_code'],
            $entry['description'],
            $this->formatAmount((float) $entry['debit']),
            $this->formatAmount((float) $entry['credit']),
            $entry['currency'],
        ]);
    }

    /**
     * Format a generic row.
     *
     * @param  array<string, mixed>  $entry
     */
    private function formatGenericRow(array $entry): string
    {
        return implode(';', [
            $entry['date'],
            $entry['piece_ref'],
            $entry['account_code'],
            $entry['account_name'],
            $entry['description'],
            $this->formatAmount((float) $entry['debit']),
            $this->formatAmount((float) $entry['credit']),
            $entry['currency'],
        ]);
    }

    /**
     * Format amount with French decimal notation.
     */
    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, ',', '');
    }
}
