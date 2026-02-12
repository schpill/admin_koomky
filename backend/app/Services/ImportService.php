<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ImportClientJob;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

final class ImportService
{
    public function __construct(
        private ReferenceGeneratorService $referenceGenerator
    ) {}

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
                return new ImportClientJob($user, $row);
            })
        )->dispatch();

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
     * @return array<array<string, string|null>>
     */
    private function parseCsv(string $filePath): array
    {
        $rows = [];

        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle, 1000, ',');

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $row = array_combine($headers, $data);

                // Skip rows without at least company_name or first_name
                if (empty($row['company_name'] ?? '') && empty($row['first_name'] ?? '')) {
                    continue;
                }

                $rows[] = [
                    'company_name' => $row['company_name'] ?? null,
                    'first_name' => $row['first_name'] ?? null,
                    'last_name' => $row['last_name'] ?? null,
                    'email' => $row['email'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'vat_number' => $row['vat_number'] ?? null,
                    'website' => $row['website'] ?? null,
                    'address' => $row['address'] ?? null,
                    'city' => $row['city'] ?? null,
                    'postal_code' => $row['postal_code'] ?? null,
                    'country' => $row['country'] ?? null,
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
        $headers = ['company_name', 'first_name', 'last_name', 'email', 'phone', 'vat_number', 'website', 'address', 'city', 'postal_code', 'country', 'notes'];

        $fp = fopen('php://temp', 'w+');
        fputcsv($fp, $headers);

        // Add example row
        fputcsv($fp, [
            'Acme Corporation',
            'John',
            'Doe',
            'contact@acme.com',
            '+33 1 23 45 67 89',
            'FR12345678901',
            'https://acme.com',
            '123 Business Street',
            'Paris',
            '75001',
            'France',
            'Premium client - prioritize requests',
        ]);

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv;
    }
}
