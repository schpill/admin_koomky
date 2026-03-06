<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Contact;
use App\Models\ImportSession;
use App\Models\ImportSessionError;
use App\Models\Tag;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProspectImportService
{
    public function __construct(private readonly FileParserService $fileParserService) {}

    public function import(ImportSession $session): void
    {
        $extension = strtolower(pathinfo($session->original_filename, PATHINFO_EXTENSION));
        $path = Storage::disk('local')->path($session->filename);

        $parsed = $this->fileParserService->parse($path, $extension);

        $mapping = is_array($session->column_mapping) ? $session->column_mapping : [];
        $defaultTags = is_array($session->default_tags) ? $session->default_tags : [];
        $options = is_array($session->options) ? $session->options : [];

        $duplicateStrategy = (string) ($options['duplicate_strategy'] ?? 'skip');
        $defaultStatus = (string) ($options['default_status'] ?? 'prospect');
        if (! in_array($defaultStatus, ['prospect', 'lead', 'active'], true)) {
            $defaultStatus = 'prospect';
        }

        $session->forceFill([
            'status' => 'processing',
            'total_rows' => count($parsed['rows']),
            'processed_rows' => 0,
            'success_rows' => 0,
            'error_rows' => 0,
            'error_summary' => null,
        ])->save();

        $processed = 0;
        $success = 0;
        $errors = 0;

        foreach ($parsed['rows'] as $index => $row) {
            $processed++;
            $rowNumber = $index + 2;

            try {
                $payload = $this->buildPayload($row, $mapping);
                $client = $this->upsertClient($session, $payload, $duplicateStrategy, $defaultStatus);

                $this->upsertPrimaryContact($client, $payload);
                $this->applyDefaultTags($client, $session->user_id, $defaultTags);

                $success++;
            } catch (RuntimeException $exception) {
                ImportSessionError::query()->create([
                    'session_id' => $session->id,
                    'row_number' => $rowNumber,
                    'raw_data' => $row,
                    'error_message' => $exception->getMessage(),
                ]);
                $errors++;
            }

            if ($processed % 50 === 0) {
                $session->forceFill([
                    'processed_rows' => $processed,
                    'success_rows' => $success,
                    'error_rows' => $errors,
                ])->save();
            }
        }

        $session->forceFill([
            'processed_rows' => $processed,
            'success_rows' => $success,
            'error_rows' => $errors,
            'status' => $success > 0 ? 'completed' : 'failed',
            'completed_at' => now(),
        ])->save();
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  array<string, string|null>  $mapping
     * @return array{client: array<string, string|null>, contact: array<string, string|null>}
     */
    private function buildPayload(array $row, array $mapping): array
    {
        $client = [
            'name' => null,
            'email' => null,
            'phone' => null,
            'address' => null,
            'city' => null,
            'zip_code' => null,
            'country' => null,
            'department' => null,
            'industry' => null,
            'notes' => null,
        ];

        $contact = [
            'first_name' => null,
            'last_name' => null,
            'position' => null,
        ];

        foreach ($mapping as $header => $field) {
            if (! is_string($field) || $field === '') {
                continue;
            }

            $value = $row[$header] ?? null;
            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            if (array_key_exists($field, $client)) {
                $client[$field] = trim($value);

                continue;
            }

            if ($field === 'contact.first_name') {
                $contact['first_name'] = trim($value);
            } elseif ($field === 'contact.last_name') {
                $contact['last_name'] = trim($value);
            } elseif ($field === 'contact.position') {
                $contact['position'] = trim($value);
            }
        }

        return ['client' => $client, 'contact' => $contact];
    }

    /**
     * @param  array{client: array<string, string|null>, contact: array<string, string|null>}  $payload
     */
    private function upsertClient(ImportSession $session, array $payload, string $duplicateStrategy, string $defaultStatus): Client
    {
        $clientData = $payload['client'];

        if (! is_string($clientData['name']) || trim($clientData['name']) === '') {
            throw new RuntimeException('Client name is required.');
        }

        if (is_string($clientData['email']) && ! filter_var($clientData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Email is invalid.');
        }

        $existingClient = null;

        if (is_string($clientData['email']) && $clientData['email'] !== '') {
            $existingClient = Client::query()
                ->where('user_id', $session->user_id)
                ->where('email', $clientData['email'])
                ->first();
        } elseif (is_string($clientData['phone']) && $clientData['phone'] !== '') {
            $existingClient = Client::query()
                ->where('user_id', $session->user_id)
                ->where('name', $clientData['name'])
                ->where('phone', $clientData['phone'])
                ->first();
        }

        $attributes = array_merge($clientData, ['status' => $defaultStatus]);

        if ($existingClient !== null) {
            if ($duplicateStrategy === 'skip') {
                throw new RuntimeException('Duplicate client detected and skipped.');
            }

            $existingClient->update($attributes);

            return $existingClient->fresh() ?? $existingClient;
        }

        return Client::query()->create([
            'user_id' => $session->user_id,
            'reference' => ReferenceGenerator::generate('clients', 'CLI'),
            ...$attributes,
        ]);
    }

    /**
     * @param  array{client: array<string, string|null>, contact: array<string, string|null>}  $payload
     */
    private function upsertPrimaryContact(Client $client, array $payload): void
    {
        $contactData = $payload['contact'];

        if (($contactData['first_name'] ?? null) === null && ($contactData['last_name'] ?? null) === null) {
            return;
        }

        Contact::query()->create([
            'client_id' => $client->id,
            'first_name' => $contactData['first_name'] ?? '',
            'last_name' => $contactData['last_name'] ?? '',
            'position' => $contactData['position'] ?? null,
            'email' => $client->email,
            'phone' => $client->phone,
            'is_primary' => true,
            'email_consent' => false,
            'sms_consent' => false,
        ]);
    }

    /**
     * @param  array<int, string>  $defaultTags
     */
    private function applyDefaultTags(Client $client, string $userId, array $defaultTags): void
    {
        if ($defaultTags === []) {
            return;
        }

        foreach ($defaultTags as $tagName) {
            if (trim($tagName) === '') {
                continue;
            }

            $tag = Tag::query()->firstOrCreate(
                ['user_id' => $userId, 'name' => trim($tagName)],
                ['color' => '#6366f1']
            );

            $client->tags()->syncWithoutDetaching([$tag->id]);
        }
    }
}
