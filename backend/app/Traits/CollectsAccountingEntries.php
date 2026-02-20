<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Shared accounting entry collection logic.
 *
 * Provides methods to query and normalise entries (invoices, credit notes,
 * payments, expenses) into a common structure consumed by FEC and CSV export
 * services.  Each entry is a typed array-shape so callers can rely on the
 * keys without casting.
 *
 * Entry shape (all keys always present):
 *
 * @phpstan-type AccountingEntry array{
 *     date: string,
 *     date_ymd: string,
 *     piece_ref: string,
 *     account_code: string,
 *     account_name: string,
 *     description: string,
 *     debit: float,
 *     credit: float,
 *     currency: string,
 *     client_ref: int|string|null,
 *     client_aux_id: string,
 *     client_name: string,
 *     journal_type: string,
 *     entry_suffix: string,
 * }
 */
trait CollectsAccountingEntries
{
    // ---------------------------------------------------------------------------
    // Account codes (French Plan Comptable Général)
    // ---------------------------------------------------------------------------

    private const ACC_CLIENT = '411000';

    private const ACC_REVENUE = '706000';

    private const ACC_VAT_COLLECTED = '445710';

    private const ACC_VAT_DEDUCTIBLE = '445660';

    private const ACC_BANK = '512000';

    private const ACC_SUPPLIER = '401000';

    private const ACC_DEFAULT_CHARGE = '622600';

    // ---------------------------------------------------------------------------
    // Format helper
    // ---------------------------------------------------------------------------

    /**
     * Format a monetary amount with French decimal notation (comma separator).
     */
    protected function formatAmount(float $amount): string
    {
        return number_format($amount, 2, ',', '');
    }

    // ---------------------------------------------------------------------------
    // Entry collectors
    // ---------------------------------------------------------------------------

    /**
     * Collect all accounting entries sorted by date.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function collectAllEntries(User $user, Carbon $dateFrom, Carbon $dateTo): Collection
    {
        $entries = collect();
        $entries = $entries->merge($this->collectInvoiceEntries($user, $dateFrom, $dateTo));
        $entries = $entries->merge($this->collectCreditNoteEntries($user, $dateFrom, $dateTo));
        $entries = $entries->merge($this->collectPaymentEntries($user, $dateFrom, $dateTo));
        $entries = $entries->merge($this->collectExpenseEntries($user, $dateFrom, $dateTo));

        return $entries->sortBy('date')->values();
    }

    /**
     * Collect normalised invoice entries.
     *
     * Each invoice produces 2 entries (client debit + revenue credit) plus an
     * optional 3rd entry when VAT is non-zero.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function collectInvoiceEntries(User $user, Carbon $dateFrom, Carbon $dateTo): Collection
    {
        $entries = collect();
        $auxPrefix = (string) ($user->accounting_auxiliary_prefix ?? '');
        $journalCode = (string) ($user->accounting_journal_sales ?? 'VTE');

        $invoices = Invoice::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'draft')
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->with(['client', 'lineItems'])
            ->orderBy('issue_date')
            ->orderBy('number')
            ->get();

        foreach ($invoices as $invoice) {
            $client = $invoice->client;
            $clientName = (string) ($client->name ?? 'Client inconnu');
            $clientRef = $client->id ?? null;
            $clientAuxId = $auxPrefix.($clientRef ?? '');
            $currency = (string) ($invoice->currency ?? 'EUR');

            $total = (float) ($invoice->base_currency_total ?? $invoice->total);
            $taxAmount = (float) $invoice->tax_amount;
            $revenue = $total - $taxAmount;

            $dateYmd = $invoice->issue_date->format('Ymd');
            $dateIso = $invoice->issue_date->format('Y-m-d');
            $pieceRef = (string) $invoice->number;
            $descInvoice = substr('Facture '.$invoice->number.' - '.$clientName, 0, 50);
            $descRevenue = substr('Facture '.$invoice->number, 0, 50);
            $descVat = substr('TVA Facture '.$invoice->number, 0, 50);

            // Entry 1: Debit client account
            $entries->push($this->makeEntry(
                dateIso: $dateIso,
                dateYmd: $dateYmd,
                pieceRef: $pieceRef,
                accountCode: self::ACC_CLIENT,
                accountName: 'Clients',
                description: $descInvoice,
                debit: $total,
                credit: 0.0,
                currency: $currency,
                clientRef: $clientRef,
                clientAuxId: $clientAuxId,
                clientName: $clientName,
                journalType: 'sales',
                journalCode: $journalCode,
                entrySuffix: $invoice->number.'-1',
            ));

            // Entry 2: Credit revenue account
            $entries->push($this->makeEntry(
                dateIso: $dateIso,
                dateYmd: $dateYmd,
                pieceRef: $pieceRef,
                accountCode: self::ACC_REVENUE,
                accountName: 'Prestations de services',
                description: $descRevenue,
                debit: 0.0,
                credit: $revenue,
                currency: $currency,
                clientRef: null,
                clientAuxId: '',
                clientName: '',
                journalType: 'sales',
                journalCode: $journalCode,
                entrySuffix: $invoice->number.'-2',
            ));

            // Entry 3: Credit VAT collected (if applicable)
            if ($taxAmount > 0.0) {
                $entries->push($this->makeEntry(
                    dateIso: $dateIso,
                    dateYmd: $dateYmd,
                    pieceRef: $pieceRef,
                    accountCode: self::ACC_VAT_COLLECTED,
                    accountName: 'TVA collectée',
                    description: $descVat,
                    debit: 0.0,
                    credit: $taxAmount,
                    currency: $currency,
                    clientRef: null,
                    clientAuxId: '',
                    clientName: '',
                    journalType: 'sales',
                    journalCode: $journalCode,
                    entrySuffix: $invoice->number.'-3',
                ));
            }
        }

        return $entries;
    }

    /**
     * Collect normalised credit note entries.
     *
     * Each credit note produces 2 reversed entries plus an optional 3rd when VAT
     * is non-zero.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function collectCreditNoteEntries(User $user, Carbon $dateFrom, Carbon $dateTo): Collection
    {
        $entries = collect();
        $auxPrefix = (string) ($user->accounting_auxiliary_prefix ?? '');
        $journalCode = (string) ($user->accounting_journal_sales ?? 'VTE');

        $creditNotes = CreditNote::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'draft')
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->with(['client'])
            ->orderBy('issue_date')
            ->orderBy('number')
            ->get();

        foreach ($creditNotes as $creditNote) {
            $client = $creditNote->client;
            $clientName = (string) ($client->name ?? 'Client inconnu');
            $clientRef = $client->id ?? null;
            $clientAuxId = $auxPrefix.($clientRef ?? '');
            $currency = (string) ($creditNote->currency ?? 'EUR');

            $total = (float) ($creditNote->base_currency_total ?? $creditNote->total);
            $taxAmount = (float) $creditNote->tax_amount;
            $revenue = $total - $taxAmount;

            $dateYmd = $creditNote->issue_date->format('Ymd');
            $dateIso = $creditNote->issue_date->format('Y-m-d');
            $pieceRef = (string) $creditNote->number;
            $descClient = substr('Avoir '.$creditNote->number.' - '.$clientName, 0, 50);
            $descRevenue = substr('Avoir '.$creditNote->number, 0, 50);
            $descVat = substr('TVA Avoir '.$creditNote->number, 0, 50);

            // Entry 1: Credit client account (reverse)
            $entries->push($this->makeEntry(
                dateIso: $dateIso,
                dateYmd: $dateYmd,
                pieceRef: $pieceRef,
                accountCode: self::ACC_CLIENT,
                accountName: 'Clients',
                description: $descClient,
                debit: 0.0,
                credit: $total,
                currency: $currency,
                clientRef: $clientRef,
                clientAuxId: $clientAuxId,
                clientName: $clientName,
                journalType: 'sales',
                journalCode: $journalCode,
                entrySuffix: $creditNote->number.'-1',
            ));

            // Entry 2: Debit revenue account (reverse)
            $entries->push($this->makeEntry(
                dateIso: $dateIso,
                dateYmd: $dateYmd,
                pieceRef: $pieceRef,
                accountCode: self::ACC_REVENUE,
                accountName: 'Prestations de services',
                description: $descRevenue,
                debit: $revenue,
                credit: 0.0,
                currency: $currency,
                clientRef: null,
                clientAuxId: '',
                clientName: '',
                journalType: 'sales',
                journalCode: $journalCode,
                entrySuffix: $creditNote->number.'-2',
            ));

            // Entry 3: Debit VAT collected (reverse, if applicable)
            if ($taxAmount > 0.0) {
                $entries->push($this->makeEntry(
                    dateIso: $dateIso,
                    dateYmd: $dateYmd,
                    pieceRef: $pieceRef,
                    accountCode: self::ACC_VAT_COLLECTED,
                    accountName: 'TVA collectée',
                    description: $descVat,
                    debit: $taxAmount,
                    credit: 0.0,
                    currency: $currency,
                    clientRef: null,
                    clientAuxId: '',
                    clientName: '',
                    journalType: 'sales',
                    journalCode: $journalCode,
                    entrySuffix: $creditNote->number.'-3',
                ));
            }
        }

        return $entries;
    }

    /**
     * Collect normalised payment entries.
     *
     * Each payment produces 2 entries: bank debit and client credit.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function collectPaymentEntries(User $user, Carbon $dateFrom, Carbon $dateTo): Collection
    {
        $entries = collect();
        $auxPrefix = (string) ($user->accounting_auxiliary_prefix ?? '');
        $journalCode = (string) ($user->accounting_journal_bank ?? 'BQ');

        $payments = Payment::query()
            ->whereHas('invoice', function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->with(['invoice.client'])
            ->orderBy('payment_date')
            ->orderBy('reference')
            ->get();

        foreach ($payments as $payment) {
            $invoice = $payment->invoice;
            if (! $invoice) {
                continue;
            }

            $client = $invoice->client;
            $clientName = (string) ($client->name ?? 'Client inconnu');
            $clientRef = $client->id ?? null;
            $clientAuxId = $auxPrefix.($clientRef ?? '');
            $currency = (string) ($invoice->currency ?? 'EUR');
            $invoiceNumber = (string) ($invoice->number ?? 'N/A');
            $pieceRef = (string) ($payment->reference ?? 'PMT-'.$payment->id);

            $amount = (float) $payment->amount;

            $dateYmd = $payment->payment_date->format('Ymd');
            $dateIso = $payment->payment_date->format('Y-m-d');
            $descBank = substr('Encaissement '.$invoiceNumber, 0, 50);
            $descClient = substr('Encaissement '.$invoiceNumber.' - '.$clientName, 0, 50);

            // Entry 1: Debit bank account
            $entries->push($this->makeEntry(
                dateIso: $dateIso,
                dateYmd: $dateYmd,
                pieceRef: $pieceRef,
                accountCode: self::ACC_BANK,
                accountName: 'Banque',
                description: $descBank,
                debit: $amount,
                credit: 0.0,
                currency: $currency,
                clientRef: null,
                clientAuxId: '',
                clientName: '',
                journalType: 'bank',
                journalCode: $journalCode,
                entrySuffix: 'BQ-'.$payment->id.'-1',
            ));

            // Entry 2: Credit client account
            $entries->push($this->makeEntry(
                dateIso: $dateIso,
                dateYmd: $dateYmd,
                pieceRef: $pieceRef,
                accountCode: self::ACC_CLIENT,
                accountName: 'Clients',
                description: $descClient,
                debit: 0.0,
                credit: $amount,
                currency: $currency,
                clientRef: $clientRef,
                clientAuxId: $clientAuxId,
                clientName: $clientName,
                journalType: 'bank',
                journalCode: $journalCode,
                entrySuffix: 'BQ-'.$payment->id.'-2',
            ));
        }

        return $entries;
    }

    /**
     * Collect normalised expense entries.
     *
     * Each expense produces 2 entries (charge debit + supplier/bank credit) plus
     * an optional VAT debit entry.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function collectExpenseEntries(User $user, Carbon $dateFrom, Carbon $dateTo): Collection
    {
        $entries = collect();
        $journalCode = (string) ($user->accounting_journal_purchases ?? 'ACH');

        $expenses = Expense::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->with(['category'])
            ->orderBy('date')
            ->orderBy('description')
            ->get();

        foreach ($expenses as $expense) {
            $amount = (float) ($expense->base_currency_amount ?? $expense->amount);
            $taxAmount = (float) $expense->tax_amount;
            $chargeAmount = $amount - $taxAmount;

            $chargeAccount = ($expense->category && $expense->category->account_code)
                ? (string) $expense->category->account_code
                : self::ACC_DEFAULT_CHARGE;
            $chargeName = $expense->category
                ? (string) $expense->category->name
                : 'Charges diverses';

            $creditAccount = $expense->payment_method === 'bank_transfer'
                ? self::ACC_BANK
                : self::ACC_SUPPLIER;
            $creditName = $expense->payment_method === 'bank_transfer'
                ? 'Banque'
                : 'Fournisseurs';

            $vendor = (string) ($expense->vendor ?? 'Fournisseur');
            $currency = (string) ($expense->currency ?? 'EUR');
            $pieceRef = (string) ($expense->reference ?? 'EXP-'.$expense->id);

            $dateYmd = $expense->date->format('Ymd');
            $dateIso = $expense->date->format('Y-m-d');
            $descCharge = substr((string) $expense->description, 0, 50);
            $descVat = substr('TVA '.(string) $expense->description, 0, 50);
            $descCredit = substr($vendor.' - '.(string) $expense->description, 0, 50);

            // Entry 1: Debit charge account
            $entries->push($this->makeEntry(
                dateIso: $dateIso,
                dateYmd: $dateYmd,
                pieceRef: $pieceRef,
                accountCode: $chargeAccount,
                accountName: $chargeName,
                description: $descCharge,
                debit: $chargeAmount,
                credit: 0.0,
                currency: $currency,
                clientRef: null,
                clientAuxId: '',
                clientName: '',
                journalType: 'purchases',
                journalCode: $journalCode,
                entrySuffix: 'ACH-'.$expense->id.'-1',
            ));

            // Entry 2: Debit VAT deductible (if applicable)
            if ($taxAmount > 0.0) {
                $entries->push($this->makeEntry(
                    dateIso: $dateIso,
                    dateYmd: $dateYmd,
                    pieceRef: $pieceRef,
                    accountCode: self::ACC_VAT_DEDUCTIBLE,
                    accountName: 'TVA déductible',
                    description: $descVat,
                    debit: $taxAmount,
                    credit: 0.0,
                    currency: $currency,
                    clientRef: null,
                    clientAuxId: '',
                    clientName: '',
                    journalType: 'purchases',
                    journalCode: $journalCode,
                    entrySuffix: 'ACH-'.$expense->id.'-2',
                ));
            }

            // Entry 3: Credit supplier or bank account
            $entries->push($this->makeEntry(
                dateIso: $dateIso,
                dateYmd: $dateYmd,
                pieceRef: $pieceRef,
                accountCode: $creditAccount,
                accountName: $creditName,
                description: $descCredit,
                debit: 0.0,
                credit: $amount,
                currency: $currency,
                clientRef: null,
                clientAuxId: '',
                clientName: '',
                journalType: 'purchases',
                journalCode: $journalCode,
                entrySuffix: 'ACH-'.$expense->id.'-3',
            ));
        }

        return $entries;
    }

    // ---------------------------------------------------------------------------
    // Entry counting helpers (SQL aggregation — no full record loading)
    // ---------------------------------------------------------------------------

    /**
     * Count FEC-style entries for invoices (2 per invoice + 1 if VAT > 0).
     */
    protected function countInvoiceEntries(User $user, Carbon $dateFrom, Carbon $dateTo): int
    {
        $base = Invoice::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'draft')
            ->whereBetween('issue_date', [$dateFrom, $dateTo]);

        $total = $base->count();
        $withTax = (int) $base->where('tax_amount', '>', 0)->count();

        return ($total * 2) + $withTax;
    }

    /**
     * Count FEC-style entries for credit notes (2 per credit note + 1 if VAT > 0).
     */
    protected function countCreditNoteEntries(User $user, Carbon $dateFrom, Carbon $dateTo): int
    {
        $base = CreditNote::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'draft')
            ->whereBetween('issue_date', [$dateFrom, $dateTo]);

        $total = $base->count();
        $withTax = (int) $base->where('tax_amount', '>', 0)->count();

        return ($total * 2) + $withTax;
    }

    /**
     * Count FEC-style entries for payments (2 per payment).
     */
    protected function countPaymentEntries(User $user, Carbon $dateFrom, Carbon $dateTo): int
    {
        return Payment::query()
            ->whereHas('invoice', function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->count() * 2;
    }

    /**
     * Count FEC-style entries for expenses (2 per expense + 1 if VAT > 0).
     */
    protected function countExpenseEntries(User $user, Carbon $dateFrom, Carbon $dateTo): int
    {
        $base = Expense::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$dateFrom, $dateTo]);

        $total = $base->count();
        $withTax = (int) $base->where('tax_amount', '>', 0)->count();

        return ($total * 2) + $withTax;
    }

    // ---------------------------------------------------------------------------
    // Internal factory
    // ---------------------------------------------------------------------------

    /**
     * Build a normalised entry array.
     *
     * All keys are guaranteed to be present so callers never need isset() checks.
     *
     * @return array<string, mixed>
     */
    private function makeEntry(
        string $dateIso,
        string $dateYmd,
        string $pieceRef,
        string $accountCode,
        string $accountName,
        string $description,
        float $debit,
        float $credit,
        string $currency,
        int|string|null $clientRef,
        string $clientAuxId,
        string $clientName,
        string $journalType,
        string $journalCode,
        string $entrySuffix,
    ): array {
        return [
            'date' => $dateIso,
            'date_ymd' => $dateYmd,
            'piece_ref' => $pieceRef,
            'account_code' => $accountCode,
            'account_name' => $accountName,
            'description' => $description,
            'debit' => $debit,
            'credit' => $credit,
            'currency' => $currency,
            'client_ref' => $clientRef,
            'client_aux_id' => $clientAuxId,
            'client_name' => $clientName,
            'journal_type' => $journalType,
            'journal_code' => $journalCode,
            'entry_suffix' => $entrySuffix,
        ];
    }
}
