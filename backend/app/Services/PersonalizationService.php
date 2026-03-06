<?php

namespace App\Services;

use App\Models\Contact;

class PersonalizationService
{
    public function render(string $content, Contact $contact): string
    {
        $client = $contact->client;
        $baseVariables = [
            '{{first_name}}' => e((string) $contact->first_name),
            '{{last_name}}' => e((string) ($contact->last_name ?? '')),
            '{{company}}' => e((string) ($client?->name ?? '')),
            '{{email}}' => e((string) ($contact->email ?? '')),
            '{{phone}}' => e((string) ($contact->phone ?? '')),
        ];

        return $this->renderWithContext($content, $baseVariables, $contact, $client);
    }

    public function renderPreview(string $content): string
    {
        $previewContact = [
            'first_name' => 'Marie',
            'last_name' => 'Dupont',
            'email' => 'marie.dupont@example.com',
            'phone' => '+33 6 12 34 56 78',
            'position' => 'Directrice',
        ];

        $previewClient = [
            'name' => 'Acme Corp',
            'email' => '',
            'phone' => '',
            'city' => 'Paris',
            'country' => 'France',
            'address' => '12 rue de la Paix',
            'zip_code' => '75001',
            'industry' => 'Wedding Planner',
            'department' => '75',
            'reference' => 'REF-001',
        ];

        $baseVariables = [
            '{{first_name}}' => e($previewContact['first_name']),
            '{{last_name}}' => e($previewContact['last_name']),
            '{{company}}' => e($previewClient['name']),
            '{{email}}' => e($previewContact['email']),
            '{{phone}}' => e($previewContact['phone']),
        ];

        return $this->renderWithContext($content, $baseVariables, $previewContact, $previewClient);
    }

    /**
     * @param  array<string, string>  $baseVariables
     * @param  Contact|array<string, string>  $contact
     * @param  \App\Models\Client|array<string, string>|null  $client
     */
    private function renderWithContext(string $content, array $baseVariables, Contact|array $contact, mixed $client): string
    {
        $content = strtr($content, $baseVariables);

        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', function (array $matches) use ($contact, $client): string {
            $key = (string) $matches[1];

            if (str_starts_with($key, 'contact.')) {
                $field = substr($key, 8);
                if (in_array($field, ['first_name', 'last_name', 'email', 'phone', 'position'], true)) {
                    return e((string) $this->resolveField($contact, $field));
                }
            }

            if (str_starts_with($key, 'client.')) {
                $field = substr($key, 7);
                if (in_array($field, ['name', 'email', 'phone', 'city', 'country', 'address', 'zip_code', 'industry', 'department', 'reference'], true)) {
                    return e((string) $this->resolveField($client, $field));
                }
            }

            return '';
        }, $content) ?? $content;
    }

    /**
     * @param  Contact|array<string, string>|\App\Models\Client|null  $source
     */
    private function resolveField(mixed $source, string $field): string
    {
        if (is_array($source)) {
            return (string) ($source[$field] ?? '');
        }

        if ($source === null) {
            return '';
        }

        return (string) ($source->{$field} ?? '');
    }
}
