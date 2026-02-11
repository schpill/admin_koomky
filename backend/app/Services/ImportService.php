<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Validator;

final readonly class ImportService
{
    public function __construct(
        private ReferenceGeneratorService $referenceGenerator
    ) {
    }

    /**
     * Import clients from CSV file.
     *
     * @return array{total: int, success: int, failed: int, batch_id: string}
     */
    public function importClientsFromCsv(User $user, string $filePath): array
    {
        $csvData = $this->parseCsv($filePath);

        $batch = Bus::batch(
            collect($csvData)->map(function ($row) use ($user) {
                return new Jobs\ImportClientJob($user, $row);
            })
        );

        return [
            'total' => count($csvData),
            'success' => $batch->totalJobs - $batch->failedJobs,
            'failed' => $batch->failedJobs,
            'batch_id' => $batch->id,
        ];
    }

    /**
     * Parse CSV file into array.
     *
     * @return array<array<string, string>>
     */
    private function parseCsv(string $filePath): array
    {
        $rows = [];

        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle, 1000, ',');

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $row = array_combine($headers, $data);

                // Validate required fields
                if (empty($row['name'] ?? '')) {
                    continue;
                }

                $rows[] = [
                    'name' => $row['name'] ?? '',
                    'email' => $row['email'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'company' => $row['company'] ?? null,
                    'vat_number' => $row['vat_number'] ?? null,
                    'website' => $row['website'] ?? null,
                    'billing_address' => $row['billing_address'] ?? null,
                    'notes' => $row['notes'] ?? null,
                ];
            }

            fclose($handle);
        }

        return $rows;
    }

    /**
     * Generate CSV template for import.
     */
    public function generateCsvTemplate(): string
    {
        $headers = ['name', 'email', 'phone', 'company', 'vat_number', 'website', 'billing_address', 'notes'];

        $fp = fopen('php://temp', 'w+');
        fputcsv($fp, $headers);

        // Add example row
        fputcsv($fp, [
            'Acme Corporation',
            'contact@acme.com',
            '+33 1 23 45 67 89',
            'Acme Corp',
            'FR12345678901',
            'https://acme.com',
            '123 Business Street\n75001 Paris\nFrance',
            'Premium client - prioritize requests',
        ]);

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv;
    }
}
