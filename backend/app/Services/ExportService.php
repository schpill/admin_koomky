<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Collection;

final readonly class ExportService
{
    /**
     * Export clients to CSV.
     */
    public function exportClientsToCsv(Collection $clients, string $fileName): string
    {
        $filePath = storage_path("app/temp/{$fileName}.csv");

        $fp = fopen($filePath, 'w+');

        // Write headers
        fputcsv($fp, [
            'Reference',
            'Name',
            'Email',
            'Phone',
            'Company',
            'VAT Number',
            'Website',
            'Billing Address',
            'Notes',
            'Status',
            'Created At',
        ]);

        // Write data
        foreach ($clients as $client) {
            fputcsv($fp, [
                $client->reference,
                $client->name,
                $client->email ?? '',
                $client->phone ?? '',
                $client->company ?? '',
                $client->vat_number ?? '',
                $client->website ?? '',
                $this->formatNewlines($client->billing_address),
                $this->formatNewlines($client->notes),
                $client->status,
                $client->created_at?->toIso8601String(),
            ]);
        }

        fclose($fp);

        return $filePath;
    }

    /**
     * Format newlines for CSV export.
     */
    protected function formatNewlines(?string $text): string
    {
        return $text ? str_replace(["\r\n", "\r", "\n"], ' ', $text) : '';
    }
}
