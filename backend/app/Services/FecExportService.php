<?php

namespace App\Services;

use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Service for generating FEC (Fichier des Écritures Comptables) compliant exports.
 *
 * FEC is the French tax authority standard for accounting data export.
 * Format: semicolon-delimited UTF-8 file with specific column requirements.
 */
class FecExportService
{
    /**
     * Default account codes for French Plan Comptable Général.
     */
    private const ACCOUNT_CLIENT = '411000';

    private const ACCOUNT_REVENUE = '706000';

    private const ACCOUNT_VAT_COLLECTED = '445710';

    private const ACCOUNT_VAT_DEDUCTIBLE = '445660';

    private const ACCOUNT_BANK = '512000';

    private const ACCOUNT_SUPPLIER = '401000';

    /**
     * @param  array<string, mixed>  $options
     * @return \Generator<int, string>
     */
    public function generate(User $user, array $options = []): \Generator
    {
        $dateFrom = Carbon::parse($options['date_from'] ?? now()->startOfYear());
        $dateTo = Carbon::parse($options['date_to'] ?? now()->endOfYear());

        // Yield header row
        yield $this->buildHeaderRow();

        // Yield invoice entries
        foreach ($this->getInvoiceEntries($user, $dateFrom, $dateTo) as $entry) {
            yield $entry;
        }

        // Yield credit note entries
        foreach ($this->getCreditNoteEntries($user, $dateFrom, $dateTo) as $entry) {
            yield $entry;
        }

        // Yield payment entries
        foreach ($this->getPaymentEntries($user, $dateFrom, $dateTo) as $entry) {
            yield $entry;
        }

        // Yield expense entries
        foreach ($this->getExpenseEntries($user, $dateFrom, $dateTo) as $entry) {
            yield $entry;
        }
    }

    /**
     * Get the total entry count for the given period.
     *
     * @param  array<string, mixed>  $options
     */
    public function getEntryCount(User $user, array $options = []): int
    {
        $dateFrom = Carbon::parse($options['date_from'] ?? now()->startOfYear());
        $dateTo = Carbon::parse($options['date_to'] ?? now()->endOfYear());

        $invoiceCount = $this->countInvoiceEntries($user, $dateFrom, $dateTo);
        $creditNoteCount = $this->countCreditNoteEntries($user, $dateFrom, $dateTo);
        $paymentCount = $this->countPaymentEntries($user, $dateFrom, $dateTo);
        $expenseCount = $this->countExpenseEntries($user, $dateFrom, $dateTo);

        // Add 1 for header row
        return 1 + $invoiceCount + $creditNoteCount + $paymentCount + $expenseCount;
    }

    /**
     * Build the FEC header row.
     */
    private function buildHeaderRow(): string
    {
        $columns = [
            'JournalCode',
            'JournalLib',
            'EcritureNum',
            'EcritureDate',
            'CompteNum',
            'CompteLib',
            'CompAuxNum',
            'CompAuxLib',
            'PieceRef',
            'PieceDate',
            'EcritureLib',
            'Debit',
            'Credit',
            'EcritureLet',
            'DateLet',
            'ValidDate',
            'Montantdevise',
            'Idevise',
        ];

        return implode(';', $columns);
    }

    /**
     * Get invoice accounting entries.
     *
     * @return \Generator<int, string>
     */
    private function getInvoiceEntries(User $user, Carbon $dateFrom, Carbon $dateTo): \Generator
    {
        $journalCode = $user->accounting_journal_sales ?? 'VTE';
        $auxPrefix = $user->accounting_auxiliary_prefix ?? '';

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
            $clientAuxNum = $auxPrefix.($client?->id ?? '');
            $clientName = $client?->name ?? 'Client inconnu';
            $pieceRef = $invoice->number;
            $pieceDate = $invoice->issue_date?->format('Ymd') ?? '';
            $ecritureDate = $invoice->issue_date?->format('Ymd') ?? '';
            $validDate = $invoice->issue_date?->format('Ymd') ?? '';

            $total = (float) ($invoice->base_currency_total ?? $invoice->total);
            $taxAmount = (float) $invoice->tax_amount;
            $revenue = $total - $taxAmount;

            // Entry 1: Debit client account
            yield $this->buildRow(
                journalCode: $journalCode,
                journalLib: 'Journal des ventes',
                ecritureNum: $invoice->number.'-1',
                ecritureDate: $ecritureDate,
                compteNum: self::ACCOUNT_CLIENT,
                compteLib: 'Clients',
                compAuxNum: $clientAuxNum,
                compAuxLib: $clientName,
                pieceRef: $pieceRef,
                pieceDate: $pieceDate,
                ecritureLib: substr('Facture '.$invoice->number.' - '.$clientName, 0, 50),
                debit: $total,
                credit: 0,
                validDate: $validDate
            );

            // Entry 2: Credit revenue account
            yield $this->buildRow(
                journalCode: $journalCode,
                journalLib: 'Journal des ventes',
                ecritureNum: $invoice->number.'-2',
                ecritureDate: $ecritureDate,
                compteNum: self::ACCOUNT_REVENUE,
                compteLib: 'Prestations de services',
                compAuxNum: '',
                compAuxLib: '',
                pieceRef: $pieceRef,
                pieceDate: $pieceDate,
                ecritureLib: substr('Facture '.$invoice->number, 0, 50),
                debit: 0,
                credit: $revenue,
                validDate: $validDate
            );

            // Entry 3: Credit VAT collected (if applicable)
            if ($taxAmount > 0) {
                yield $this->buildRow(
                    journalCode: $journalCode,
                    journalLib: 'Journal des ventes',
                    ecritureNum: $invoice->number.'-3',
                    ecritureDate: $ecritureDate,
                    compteNum: self::ACCOUNT_VAT_COLLECTED,
                    compteLib: 'TVA collectée',
                    compAuxNum: '',
                    compAuxLib: '',
                    pieceRef: $pieceRef,
                    pieceDate: $pieceDate,
                    ecritureLib: substr('TVA Facture '.$invoice->number, 0, 50),
                    debit: 0,
                    credit: $taxAmount,
                    validDate: $validDate
                );
            }
        }
    }

    /**
     * Get credit note accounting entries.
     *
     * @return \Generator<int, string>
     */
    private function getCreditNoteEntries(User $user, Carbon $dateFrom, Carbon $dateTo): \Generator
    {
        $journalCode = $user->accounting_journal_sales ?? 'VTE';
        $auxPrefix = $user->accounting_auxiliary_prefix ?? '';

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
            $clientAuxNum = $auxPrefix.($client?->id ?? '');
            $clientName = $client?->name ?? 'Client inconnu';
            $pieceRef = $creditNote->number;
            $pieceDate = $creditNote->issue_date?->format('Ymd') ?? '';
            $ecritureDate = $creditNote->issue_date?->format('Ymd') ?? '';
            $validDate = $creditNote->issue_date?->format('Ymd') ?? '';

            $total = (float) ($creditNote->base_currency_total ?? $creditNote->total);
            $taxAmount = (float) $creditNote->tax_amount;
            $revenue = $total - $taxAmount;

            // Entry 1: Credit client account (reverse)
            yield $this->buildRow(
                journalCode: $journalCode,
                journalLib: 'Journal des ventes',
                ecritureNum: $creditNote->number.'-1',
                ecritureDate: $ecritureDate,
                compteNum: self::ACCOUNT_CLIENT,
                compteLib: 'Clients',
                compAuxNum: $clientAuxNum,
                compAuxLib: $clientName,
                pieceRef: $pieceRef,
                pieceDate: $pieceDate,
                ecritureLib: substr('Avoir '.$creditNote->number.' - '.$clientName, 0, 50),
                debit: 0,
                credit: $total,
                validDate: $validDate
            );

            // Entry 2: Debit revenue account (reverse)
            yield $this->buildRow(
                journalCode: $journalCode,
                journalLib: 'Journal des ventes',
                ecritureNum: $creditNote->number.'-2',
                ecritureDate: $ecritureDate,
                compteNum: self::ACCOUNT_REVENUE,
                compteLib: 'Prestations de services',
                compAuxNum: '',
                compAuxLib: '',
                pieceRef: $pieceRef,
                pieceDate: $pieceDate,
                ecritureLib: substr('Avoir '.$creditNote->number, 0, 50),
                debit: $revenue,
                credit: 0,
                validDate: $validDate
            );

            // Entry 3: Debit VAT collected (reverse, if applicable)
            if ($taxAmount > 0) {
                yield $this->buildRow(
                    journalCode: $journalCode,
                    journalLib: 'Journal des ventes',
                    ecritureNum: $creditNote->number.'-3',
                    ecritureDate: $ecritureDate,
                    compteNum: self::ACCOUNT_VAT_COLLECTED,
                    compteLib: 'TVA collectée',
                    compAuxNum: '',
                    compAuxLib: '',
                    pieceRef: $pieceRef,
                    pieceDate: $pieceDate,
                    ecritureLib: substr('TVA Avoir '.$creditNote->number, 0, 50),
                    debit: $taxAmount,
                    credit: 0,
                    validDate: $validDate
                );
            }
        }
    }

    /**
     * Get payment accounting entries.
     *
     * @return \Generator<int, string>
     */
    private function getPaymentEntries(User $user, Carbon $dateFrom, Carbon $dateTo): \Generator
    {
        $journalCode = $user->accounting_journal_bank ?? 'BQ';
        $auxPrefix = $user->accounting_auxiliary_prefix ?? '';

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
            $clientAuxNum = $auxPrefix.($client?->id ?? '');
            $clientName = $client?->name ?? 'Client inconnu';
            $invoiceNumber = $invoice->number ?? 'N/A';
            $pieceRef = $payment->reference ?? 'PMT-'.$payment->id;
            $pieceDate = $payment->payment_date?->format('Ymd') ?? '';
            $ecritureDate = $payment->payment_date?->format('Ymd') ?? '';
            $validDate = $payment->payment_date?->format('Ymd') ?? '';

            $amount = (float) $payment->amount;

            // Entry 1: Debit bank account
            yield $this->buildRow(
                journalCode: $journalCode,
                journalLib: 'Journal de banque',
                ecritureNum: 'BQ-'.$payment->id.'-1',
                ecritureDate: $ecritureDate,
                compteNum: self::ACCOUNT_BANK,
                compteLib: 'Banque',
                compAuxNum: '',
                compAuxLib: '',
                pieceRef: $pieceRef,
                pieceDate: $pieceDate,
                ecritureLib: substr('Encaissement '.$invoiceNumber, 0, 50),
                debit: $amount,
                credit: 0,
                validDate: $validDate
            );

            // Entry 2: Credit client account
            yield $this->buildRow(
                journalCode: $journalCode,
                journalLib: 'Journal de banque',
                ecritureNum: 'BQ-'.$payment->id.'-2',
                ecritureDate: $ecritureDate,
                compteNum: self::ACCOUNT_CLIENT,
                compteLib: 'Clients',
                compAuxNum: $clientAuxNum,
                compAuxLib: $clientName,
                pieceRef: $pieceRef,
                pieceDate: $pieceDate,
                ecritureLib: substr('Encaissement '.$invoiceNumber.' - '.$clientName, 0, 50),
                debit: 0,
                credit: $amount,
                validDate: $validDate
            );
        }
    }

    /**
     * Get expense accounting entries.
     *
     * @return \Generator<int, string>
     */
    private function getExpenseEntries(User $user, Carbon $dateFrom, Carbon $dateTo): \Generator
    {
        $journalCode = $user->accounting_journal_purchases ?? 'ACH';

        $expenses = Expense::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->with(['category'])
            ->orderBy('date')
            ->orderBy('description')
            ->get();

        foreach ($expenses as $expense) {
            $vendor = $expense->vendor ?? 'Fournisseur';
            $pieceRef = $expense->reference ?? 'EXP-'.$expense->id;
            $pieceDate = $expense->date?->format('Ymd') ?? '';
            $ecritureDate = $expense->date?->format('Ymd') ?? '';
            $validDate = $expense->date?->format('Ymd') ?? '';

            $amount = (float) ($expense->base_currency_amount ?? $expense->amount);
            $taxAmount = (float) $expense->tax_amount;
            $chargeAmount = $amount - $taxAmount;

            // Get account code from category or use default
            $chargeAccount = ($expense->category && $expense->category->account_code)
                ? $expense->category->account_code
                : '622600';
            $chargeName = $expense->category ? $expense->category->name : 'Charges diverses';

            // Entry 1: Debit charge account
            yield $this->buildRow(
                journalCode: $journalCode,
                journalLib: 'Journal des achats',
                ecritureNum: 'ACH-'.$expense->id.'-1',
                ecritureDate: $ecritureDate,
                compteNum: $chargeAccount,
                compteLib: $chargeName,
                compAuxNum: '',
                compAuxLib: '',
                pieceRef: $pieceRef,
                pieceDate: $pieceDate,
                ecritureLib: substr($expense->description, 0, 50),
                debit: $chargeAmount,
                credit: 0,
                validDate: $validDate
            );

            // Entry 2: Debit VAT deductible (if applicable)
            if ($taxAmount > 0) {
                yield $this->buildRow(
                    journalCode: $journalCode,
                    journalLib: 'Journal des achats',
                    ecritureNum: 'ACH-'.$expense->id.'-2',
                    ecritureDate: $ecritureDate,
                    compteNum: self::ACCOUNT_VAT_DEDUCTIBLE,
                    compteLib: 'TVA déductible',
                    compAuxNum: '',
                    compAuxLib: '',
                    pieceRef: $pieceRef,
                    pieceDate: $pieceDate,
                    ecritureLib: substr('TVA '.$expense->description, 0, 50),
                    debit: $taxAmount,
                    credit: 0,
                    validDate: $validDate
                );
            }

            // Entry 3: Credit supplier or bank account
            $creditAccount = $expense->payment_method === 'bank_transfer' ? self::ACCOUNT_BANK : self::ACCOUNT_SUPPLIER;
            $creditAccountLib = $expense->payment_method === 'bank_transfer' ? 'Banque' : 'Fournisseurs';

            yield $this->buildRow(
                journalCode: $journalCode,
                journalLib: 'Journal des achats',
                ecritureNum: 'ACH-'.$expense->id.'-3',
                ecritureDate: $ecritureDate,
                compteNum: $creditAccount,
                compteLib: $creditAccountLib,
                compAuxNum: '',
                compAuxLib: '',
                pieceRef: $pieceRef,
                pieceDate: $pieceDate,
                ecritureLib: substr($vendor.' - '.$expense->description, 0, 50),
                debit: 0,
                credit: $amount,
                validDate: $validDate
            );
        }
    }

    /**
     * Count invoice entries.
     */
    private function countInvoiceEntries(User $user, Carbon $dateFrom, Carbon $dateTo): int
    {
        $invoices = Invoice::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'draft')
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->get();

        $count = 0;
        foreach ($invoices as $invoice) {
            // 2 entries minimum + 1 if VAT
            $count += ((float) $invoice->tax_amount > 0) ? 3 : 2;
        }

        return $count;
    }

    /**
     * Count credit note entries.
     */
    private function countCreditNoteEntries(User $user, Carbon $dateFrom, Carbon $dateTo): int
    {
        $creditNotes = CreditNote::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'draft')
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->get();

        $count = 0;
        foreach ($creditNotes as $creditNote) {
            // 2 entries minimum + 1 if VAT
            $count += ((float) $creditNote->tax_amount > 0) ? 3 : 2;
        }

        return $count;
    }

    /**
     * Count payment entries.
     */
    private function countPaymentEntries(User $user, Carbon $dateFrom, Carbon $dateTo): int
    {
        // Each payment has 2 entries
        return Payment::query()
            ->whereHas('invoice', function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->count() * 2;
    }

    /**
     * Count expense entries.
     */
    private function countExpenseEntries(User $user, Carbon $dateFrom, Carbon $dateTo): int
    {
        $expenses = Expense::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->get();

        $count = 0;
        foreach ($expenses as $expense) {
            // 3 entries minimum (charge, credit) + 1 if VAT
            $count += ((float) $expense->tax_amount > 0) ? 3 : 2;
        }

        return $count;
    }

    /**
     * Build a single FEC row.
     */
    private function buildRow(
        string $journalCode,
        string $journalLib,
        string $ecritureNum,
        string $ecritureDate,
        string $compteNum,
        string $compteLib,
        string $compAuxNum,
        string $compAuxLib,
        string $pieceRef,
        string $pieceDate,
        string $ecritureLib,
        float $debit,
        float $credit,
        string $validDate,
        ?float $montantdevise = null,
        ?string $idevise = null
    ): string {
        $columns = [
            $journalCode,
            $journalLib,
            $ecritureNum,
            $ecritureDate,
            $compteNum,
            $compteLib,
            $compAuxNum,
            $compAuxLib,
            $pieceRef,
            $pieceDate,
            $ecritureLib,
            $this->formatAmount($debit),
            $this->formatAmount($credit),
            '', // EcritureLet
            '', // DateLet
            $validDate,
            $montantdevise !== null ? $this->formatAmount($montantdevise) : '',
            $idevise ?? '',
        ];

        return implode(';', $columns);
    }

    /**
     * Format amount with French decimal notation (comma as decimal separator).
     */
    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, ',', '');
    }
}
