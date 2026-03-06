<?php

namespace App\Services;

use App\Models\Contact;
use InvalidArgumentException;

class PersonalizationService
{
    public function render(string $content, Contact $contact, ?string $trackingToken = null): string
    {
        $client = $contact->client;
        $baseVariables = [
            '{{first_name}}' => e((string) $contact->first_name),
            '{{last_name}}' => e((string) ($contact->last_name ?? '')),
            '{{company}}' => e($client !== null ? (string) $client->name : ''),
            '{{email}}' => e((string) ($contact->email ?? '')),
            '{{phone}}' => e((string) ($contact->phone ?? '')),
            '{{email_score}}' => e((string) $contact->email_score),
        ];

        return $this->renderWithContext($content, $baseVariables, $contact, $client, $trackingToken);
    }

    public function renderPreview(string $content): string
    {
        $previewContact = [
            'first_name' => 'Marie',
            'last_name' => 'Dupont',
            'email' => 'marie.dupont@example.com',
            'phone' => '+33 6 12 34 56 78',
            'position' => 'Directrice',
            'email_score' => 75,
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
            '{{email_score}}' => '75',
        ];

        return $this->renderWithContext($content, $baseVariables, $previewContact, $previewClient, null);
    }

    /**
     * @param  array<string, string>  $baseVariables
     * @param  Contact|array<string, string|int>  $contact
     * @param  \App\Models\Client|array<string, string>|null  $client
     */
    private function renderWithContext(string $content, array $baseVariables, Contact|array $contact, mixed $client, ?string $trackingToken): string
    {
        $content = $this->renderDynamicBlocks($content, $contact, $client, 0);
        $content = strtr($content, $baseVariables);
        $content = $this->rewriteTrackableLinks($content, $trackingToken);

        $rendered = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', function (array $matches) use ($contact, $client): string {
            $key = (string) $matches[1];

            if ($key === 'email_score') {
                return e((string) $this->resolveEmailScore($contact));
            }

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
        }, $content);

        return is_string($rendered) ? $rendered : $content;
    }

    private function rewriteTrackableLinks(string $content, ?string $trackingToken): string
    {
        if ($trackingToken === null || $trackingToken === '') {
            return $content;
        }

        $rewritten = preg_replace_callback('/href=["\']([^"\']+)["\']/i', function (array $matches) use ($trackingToken): string {
            $destination = (string) $matches[1];

            if (str_starts_with($destination, 'mailto:') || str_starts_with($destination, 'tel:')) {
                return $matches[0];
            }

            if (preg_match('#(^|/+)t/click/#', $destination) === 1) {
                return $matches[0];
            }

            $tracking = url('/t/click/'.$trackingToken).'?url='.urlencode($destination);

            return 'href="'.$tracking.'"';
        }, $content);

        return is_string($rewritten) ? $rewritten : $content;
    }

    /**
     * @param  Contact|array<string, string|int>  $contact
     * @param  \App\Models\Client|array<string, string>|null  $client
     */
    private function renderDynamicBlocks(string $content, Contact|array $contact, mixed $client, int $depth): string
    {
        if ($depth > 2) {
            return $content;
        }

        while (($start = strpos($content, '{{#if')) !== false) {
            $conditionEnd = strpos($content, '}}', $start);
            if ($conditionEnd === false) {
                break;
            }

            $condition = trim(substr($content, $start + 5, $conditionEnd - ($start + 5)));
            $block = $this->extractDynamicBlock($content, $start);
            if ($block === null) {
                break;
            }

            $branch = $this->evaluateCondition($condition, $contact, $client)
                ? $block['truthy']
                : ($block['falsy'] ?? '');

            $renderedBranch = $this->renderDynamicBlocks($branch, $contact, $client, $depth + 1);
            $content = substr($content, 0, $start).$renderedBranch.substr($content, $block['end']);
        }

        return $content;
    }

    /**
     * @param  Contact|array<string, string|int>  $contact
     * @param  \App\Models\Client|array<string, string>|null  $client
     */
    private function evaluateCondition(string $condition, Contact|array $contact, mixed $client): bool
    {
        if (! preg_match('/^(contact\.[a-zA-Z0-9_]+|client\.[a-zA-Z0-9_]+|email_score)\s*(==|!=|>=|<=|>|<)\s*(.+)$/', $condition, $matches)) {
            throw new InvalidArgumentException('Invalid dynamic condition.');
        }

        $left = $this->resolveConditionOperand($matches[1], $contact, $client);
        $right = $this->normalizeConditionValue(trim($matches[3]));

        return match ($matches[2]) {
            '==' => $left == $right,
            '!=' => $left != $right,
            '>=' => $left >= $right,
            '<=' => $left <= $right,
            '>' => $left > $right,
            '<' => $left < $right,
        };
    }

    /**
     * @param  Contact|array<string, string|int>  $contact
     * @param  \App\Models\Client|array<string, string>|null  $client
     */
    private function resolveConditionOperand(string $operand, Contact|array $contact, mixed $client): mixed
    {
        if ($operand === 'email_score') {
            return $this->resolveEmailScore($contact);
        }

        if (str_starts_with($operand, 'contact.')) {
            return $this->resolveField($contact, substr($operand, 8));
        }

        if (str_starts_with($operand, 'client.')) {
            return $this->resolveField($client, substr($operand, 7));
        }

        return null;
    }

    private function normalizeConditionValue(string $value): mixed
    {
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, '\'') && str_ends_with($value, '\''))) {
            return substr($value, 1, -1);
        }

        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }

    /**
     * @return array{truthy:string, falsy:string|null, end:int}|null
     */
    private function extractDynamicBlock(string $content, int $start): ?array
    {
        $conditionEnd = strpos($content, '}}', $start);
        if ($conditionEnd === false) {
            return null;
        }

        $cursor = $conditionEnd + 2;
        $depth = 1;
        $elsePosition = null;

        while (true) {
            $nextIf = strpos($content, '{{#if', $cursor);
            $nextElse = strpos($content, '{{else}}', $cursor);
            $nextEnd = strpos($content, '{{/if}}', $cursor);

            $candidates = array_filter([
                'if' => $nextIf,
                'else' => $nextElse,
                'end' => $nextEnd,
            ], fn ($value) => $value !== false);

            if ($candidates === []) {
                return null;
            }

            $position = min($candidates);
            $type = array_search($position, $candidates, true);

            if ($type === 'if') {
                $depth++;
                $cursor = $position + 5;

                continue;
            }

            if ($type === 'else' && $depth === 1 && $elsePosition === null) {
                $elsePosition = $position;
                $cursor = $position + 8;

                continue;
            }

            if ($type !== 'end') {
                return null;
            }

            $depth--;

            if ($depth === 0) {
                $truthyStart = $conditionEnd + 2;
                $truthyEnd = $elsePosition ?? $position;
                $falsyStart = $elsePosition !== null ? $elsePosition + 8 : null;

                return [
                    'truthy' => substr($content, $truthyStart, $truthyEnd - $truthyStart),
                    'falsy' => $falsyStart !== null ? substr($content, $falsyStart, $position - $falsyStart) : null,
                    'end' => $position + 7,
                ];
            }

            $cursor = $position + 7;
        }
    }

    /**
     * @param  Contact|array<string, string|int>|\App\Models\Client|null  $source
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

    /**
     * @param  Contact|array<string, string|int>  $contact
     */
    private function resolveEmailScore(Contact|array $contact): int
    {
        if (is_array($contact)) {
            return (int) ($contact['email_score'] ?? 75);
        }

        return (int) ($contact->email_score ?? 0);
    }
}
