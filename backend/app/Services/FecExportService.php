<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Traits\CollectsAccountingEntries;
use Illuminate\Support\Carbon;

/**
 * Service for generating FEC (Fichier des Écritures Comptables) compliant exports.
 *
 * FEC is the French tax authority standard for accounting data export.
 * Format: semicolon-delimited UTF-8 file with specific column requirements.
 *
 * Entry collection and amount calculations are provided by CollectsAccountingEntries.
 * This service is responsible only for FEC-specific formatting: column names,
 * Ymd dates, French decimal notation, EcritureNum identifiers, and streaming.
 */
class FecExportService
{
    use CollectsAccountingEntries;

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

    // ---------------------------------------------------------------------------
    // FEC-specific formatting
    // ---------------------------------------------------------------------------

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
     * Format entries from the trait's invoice collector into FEC rows.
     *
     * @return \Generator<int, string>
     */
    private function getInvoiceEntries(User $user, Carbon $dateFrom, Carbon $dateTo): \Generator
    {
        $journalLib = 'Journal des ventes';

        foreach ($this->collectInvoiceEntries($user, $dateFrom, $dateTo) as $entry) {
            yield $this->buildRow(
                journalCode: $entry['journal_code'],
                journalLib: $journalLib,
                ecritureNum: $entry['entry_suffix'],
                ecritureDate: $entry['date_ymd'],
                compteNum: $entry['account_code'],
                compteLib: $entry['account_name'],
                compAuxNum: $entry['client_aux_id'],
                compAuxLib: $entry['client_name'],
                pieceRef: $entry['piece_ref'],
                pieceDate: $entry['date_ymd'],
                ecritureLib: $entry['description'],
                debit: $entry['debit'],
                credit: $entry['credit'],
                validDate: $entry['date_ymd'],
            );
        }
    }

    /**
     * Format entries from the trait's credit note collector into FEC rows.
     *
     * @return \Generator<int, string>
     */
    private function getCreditNoteEntries(User $user, Carbon $dateFrom, Carbon $dateTo): \Generator
    {
        $journalLib = 'Journal des ventes';

        foreach ($this->collectCreditNoteEntries($user, $dateFrom, $dateTo) as $entry) {
            yield $this->buildRow(
                journalCode: $entry['journal_code'],
                journalLib: $journalLib,
                ecritureNum: $entry['entry_suffix'],
                ecritureDate: $entry['date_ymd'],
                compteNum: $entry['account_code'],
                compteLib: $entry['account_name'],
                compAuxNum: $entry['client_aux_id'],
                compAuxLib: $entry['client_name'],
                pieceRef: $entry['piece_ref'],
                pieceDate: $entry['date_ymd'],
                ecritureLib: $entry['description'],
                debit: $entry['debit'],
                credit: $entry['credit'],
                validDate: $entry['date_ymd'],
            );
        }
    }

    /**
     * Format entries from the trait's payment collector into FEC rows.
     *
     * @return \Generator<int, string>
     */
    private function getPaymentEntries(User $user, Carbon $dateFrom, Carbon $dateTo): \Generator
    {
        $journalLib = 'Journal de banque';

        foreach ($this->collectPaymentEntries($user, $dateFrom, $dateTo) as $entry) {
            yield $this->buildRow(
                journalCode: $entry['journal_code'],
                journalLib: $journalLib,
                ecritureNum: $entry['entry_suffix'],
                ecritureDate: $entry['date_ymd'],
                compteNum: $entry['account_code'],
                compteLib: $entry['account_name'],
                compAuxNum: $entry['client_aux_id'],
                compAuxLib: $entry['client_name'],
                pieceRef: $entry['piece_ref'],
                pieceDate: $entry['date_ymd'],
                ecritureLib: $entry['description'],
                debit: $entry['debit'],
                credit: $entry['credit'],
                validDate: $entry['date_ymd'],
            );
        }
    }

    /**
     * Format entries from the trait's expense collector into FEC rows.
     *
     * @return \Generator<int, string>
     */
    private function getExpenseEntries(User $user, Carbon $dateFrom, Carbon $dateTo): \Generator
    {
        $journalLib = 'Journal des achats';

        foreach ($this->collectExpenseEntries($user, $dateFrom, $dateTo) as $entry) {
            yield $this->buildRow(
                journalCode: $entry['journal_code'],
                journalLib: $journalLib,
                ecritureNum: $entry['entry_suffix'],
                ecritureDate: $entry['date_ymd'],
                compteNum: $entry['account_code'],
                compteLib: $entry['account_name'],
                compAuxNum: $entry['client_aux_id'],
                compAuxLib: $entry['client_name'],
                pieceRef: $entry['piece_ref'],
                pieceDate: $entry['date_ymd'],
                ecritureLib: $entry['description'],
                debit: $entry['debit'],
                credit: $entry['credit'],
                validDate: $entry['date_ymd'],
            );
        }
    }

    /**
     * Build a single FEC row in semicolon-delimited format.
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
}
