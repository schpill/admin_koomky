<?php

namespace App\Services;

use App\Models\Contact;

class PersonalizationService
{
    public function render(string $content, Contact $contact): string
    {
        $client = $contact->client;
        $company = $client !== null ? (string) $client->name : '';

        $variables = [
            '{{first_name}}' => e((string) $contact->first_name),
            '{{last_name}}' => e((string) ($contact->last_name ?? '')),
            '{{company}}' => e($company),
            '{{email}}' => e((string) ($contact->email ?? '')),
            '{{phone}}' => e((string) ($contact->phone ?? '')),
        ];

        $content = strtr($content, $variables);

        // Generic resolver for additional placeholders, limited to known contact/client fields.
        $content = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', function (array $matches) use ($contact, $client): string {
            $key = (string) $matches[1];

            if (str_starts_with($key, 'contact.')) {
                $field = substr($key, 8);
                if (in_array($field, ['first_name', 'last_name', 'email', 'phone', 'position'], true)) {
                    return e((string) ($contact->{$field} ?? ''));
                }
            }

            if (str_starts_with($key, 'client.')) {
                $field = substr($key, 7);
                if ($client !== null && in_array($field, ['name', 'email', 'phone', 'city', 'country', 'reference'], true)) {
                    return e((string) ($client->{$field} ?? ''));
                }
            }

            return '';
        }, $content) ?? $content;

        return $content;
    }
}
