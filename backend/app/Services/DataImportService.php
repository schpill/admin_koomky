<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\LineItem;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class DataImportService
{
    /**
     * @return array{entity:string, imported:int, errors:array<int, array<string, mixed>>}
     */
    public function import(User $user, string $entity, UploadedFile $file): array
    {
        [$headers, $rows] = $this->parseCsv($file);

        return match ($entity) {
            'projects' => $this->importProjects($user, $headers, $rows),
            'contacts' => $this->importContacts($user, $headers, $rows),
            'invoices' => $this->importInvoices($user, $headers, $rows),
            default => throw new InvalidArgumentException("Unsupported import entity [{$entity}]"),
        };
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $rows
     * @return array{entity:string, imported:int, errors:array<int, array<string, mixed>>}
     */
    private function importProjects(User $user, array $headers, array $rows): array
    {
        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $payload = $this->rowToPayload($headers, $row);

            $name = trim((string) ($payload['name'] ?? ''));
            if ($name === '') {
                $errors[] = $this->error($rowNumber, 'name', 'Project name is required');

                continue;
            }

            $clientReference = trim((string) ($payload['client_reference'] ?? ''));
            $client = Client::query()
                ->where('user_id', $user->id)
                ->where('reference', $clientReference)
                ->first();
            if (! $client) {
                $errors[] = $this->error($rowNumber, 'client_reference', 'Client reference not found');

                continue;
            }

            $billingType = (string) ($payload['billing_type'] ?? 'hourly');
            if (! in_array($billingType, ['hourly', 'fixed'], true)) {
                $billingType = 'hourly';
            }

            $status = (string) ($payload['status'] ?? 'draft');
            if (! in_array($status, ['draft', 'proposal_sent', 'in_progress', 'on_hold', 'completed', 'cancelled'], true)) {
                $status = 'draft';
            }

            Project::query()->create([
                'user_id' => $user->id,
                'client_id' => $client->id,
                'reference' => ReferenceGenerator::generate('projects', 'PRJ'),
                'name' => $name,
                'description' => $this->nullableString($payload['description'] ?? null),
                'status' => $status,
                'billing_type' => $billingType,
                'hourly_rate' => $billingType === 'hourly'
                    ? $this->nullableFloat($payload['hourly_rate'] ?? null)
                    : null,
                'fixed_price' => $billingType === 'fixed'
                    ? $this->nullableFloat($payload['fixed_price'] ?? null)
                    : null,
                'estimated_hours' => $this->nullableFloat($payload['estimated_hours'] ?? null),
                'start_date' => $this->nullableDate($payload['start_date'] ?? null),
                'deadline' => $this->nullableDate($payload['deadline'] ?? null),
            ]);

            $imported++;
        }

        return [
            'entity' => 'projects',
            'imported' => $imported,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $rows
     * @return array{entity:string, imported:int, errors:array<int, array<string, mixed>>}
     */
    private function importContacts(User $user, array $headers, array $rows): array
    {
        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $payload = $this->rowToPayload($headers, $row);

            $firstName = trim((string) ($payload['first_name'] ?? ''));
            if ($firstName === '') {
                $errors[] = $this->error($rowNumber, 'first_name', 'First name is required');

                continue;
            }

            $clientReference = trim((string) ($payload['client_reference'] ?? ''));
            $client = Client::query()
                ->where('user_id', $user->id)
                ->where('reference', $clientReference)
                ->first();
            if (! $client) {
                $errors[] = $this->error($rowNumber, 'client_reference', 'Client reference not found');

                continue;
            }

            Contact::query()->create([
                'client_id' => $client->id,
                'first_name' => $firstName,
                'last_name' => $this->nullableString($payload['last_name'] ?? null),
                'email' => $this->nullableString($payload['email'] ?? null),
                'phone' => $this->nullableString($payload['phone'] ?? null),
                'position' => $this->nullableString($payload['position'] ?? null),
                'is_primary' => (bool) ($payload['is_primary'] ?? false),
            ]);

            $imported++;
        }

        return [
            'entity' => 'contacts',
            'imported' => $imported,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $rows
     * @return array{entity:string, imported:int, errors:array<int, array<string, mixed>>}
     */
    private function importInvoices(User $user, array $headers, array $rows): array
    {
        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $payload = $this->rowToPayload($headers, $row);

            $clientReference = trim((string) ($payload['client_reference'] ?? ''));
            $client = Client::query()
                ->where('user_id', $user->id)
                ->where('reference', $clientReference)
                ->first();
            if (! $client) {
                $errors[] = $this->error($rowNumber, 'client_reference', 'Client reference not found');

                continue;
            }

            $issueDate = $this->nullableDate($payload['issue_date'] ?? null);
            $dueDate = $this->nullableDate($payload['due_date'] ?? null);
            if ($issueDate === null || $dueDate === null) {
                $errors[] = $this->error($rowNumber, 'issue_date', 'Issue date and due date are required');

                continue;
            }

            $lineDescription = $this->nullableString($payload['line_item_description'] ?? null) ?? 'Imported item';
            $quantity = $this->nullableFloat($payload['line_item_quantity'] ?? null) ?? 1.0;
            $unitPrice = $this->nullableFloat($payload['line_item_unit_price'] ?? null) ?? 0.0;
            $vatRate = $this->nullableFloat($payload['line_item_vat_rate'] ?? null) ?? 20.0;

            $subtotal = round($quantity * $unitPrice, 2);
            $taxAmount = round($subtotal * ($vatRate / 100), 2);
            $total = round($subtotal + $taxAmount, 2);

            $status = (string) ($payload['status'] ?? 'draft');
            if (! in_array($status, ['draft', 'sent', 'viewed', 'paid', 'partially_paid', 'overdue', 'cancelled'], true)) {
                $status = 'draft';
            }

            $invoice = Invoice::query()->create([
                'user_id' => $user->id,
                'client_id' => $client->id,
                'project_id' => null,
                'number' => ReferenceGenerator::generate('invoices', 'FAC'),
                'status' => $status,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_type' => null,
                'discount_value' => null,
                'discount_amount' => 0,
                'total' => $total,
                'currency' => strtoupper((string) ($payload['currency'] ?? 'EUR')),
                'notes' => $this->nullableString($payload['notes'] ?? null),
                'payment_terms' => null,
            ]);

            LineItem::query()->create([
                'documentable_type' => Invoice::class,
                'documentable_id' => $invoice->id,
                'description' => $lineDescription,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'vat_rate' => $vatRate,
                'sort_order' => 0,
            ]);

            $imported++;
        }

        return [
            'entity' => 'invoices',
            'imported' => $imported,
            'errors' => $errors,
        ];
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, array<int, string>>}
     */
    private function parseCsv(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (! is_string($path) || $path === '') {
            throw new InvalidArgumentException('Unable to read uploaded CSV file');
        }

        $content = file($path, FILE_IGNORE_NEW_LINES);
        if (! is_array($content) || count($content) === 0) {
            throw new InvalidArgumentException('CSV file is empty');
        }

        $rawHeaders = str_getcsv((string) array_shift($content));
        $headers = array_map(
            fn (?string $header): string => $this->normalizeHeader((string) $header),
            $rawHeaders
        );

        $rows = [];
        foreach ($content as $line) {
            if (trim($line) === '') {
                continue;
            }

            $parsedRow = str_getcsv($line);
            $rows[] = array_map(
                static fn (?string $value): string => (string) $value,
                $parsedRow
            );
        }

        return [$headers, $rows];
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string>  $row
     * @return array<string, string|null>
     */
    private function rowToPayload(array $headers, array $row): array
    {
        $row = array_pad($row, count($headers), null);
        /** @var array<string, string|null> $mapped */
        $mapped = array_combine($headers, $row);

        return $mapped;
    }

    private function normalizeHeader(string $header): string
    {
        return str_replace([' ', '-'], '_', strtolower(trim($header)));
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function nullableFloat(mixed $value): ?float
    {
        $stringValue = $this->nullableString($value);
        if ($stringValue === null || ! is_numeric($stringValue)) {
            return null;
        }

        return (float) $stringValue;
    }

    private function nullableDate(mixed $value): ?string
    {
        $stringValue = $this->nullableString($value);
        if ($stringValue === null) {
            return null;
        }

        try {
            return (string) Carbon::parse($stringValue)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{row:int, field:string, message:string}
     */
    private function error(int $row, string $field, string $message): array
    {
        return [
            'row' => $row,
            'field' => $field,
            'message' => $message,
        ];
    }
}
