<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Traits\CollectsAccountingEntries;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Service for generating accounting software exports.
 *
 * Supports Pennylane, Sage, and generic CSV formats.
 *
 * Entry collection and amount calculations are provided by CollectsAccountingEntries.
 * This service is responsible only for format-specific column mapping and row
 * serialisation (Pennylane, Sage, Generic CSV).
 */
class AccountingExportService
{
    use CollectsAccountingEntries;

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

        $entries = $this->collectAllEntries($user, $dateFrom, $dateTo);

        // Yield header row based on format
        yield $this->buildHeaderRow($format);

        // Yield data rows
        foreach ($entries as $entry) {
            yield $this->formatRow($entry, $format);
        }
    }

    /**
     * Get the column headers for a given format.
     *
     * @return array<int, string>
     */
    public function getColumns(string $format): array
    {
        return match ($format) {
            'pennylane' => self::PENNYLANE_COLUMNS,
            'sage' => self::SAGE_COLUMNS,
            default => self::GENERIC_COLUMNS,
        };
    }

    // ---------------------------------------------------------------------------
    // Format-specific row builders
    // ---------------------------------------------------------------------------

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
}
