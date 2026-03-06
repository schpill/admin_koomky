<?php

namespace App\Services;

class DynamicContentValidatorService
{
    /**
     * @return array{valid: bool, errors: list<string>}
     */
    public function validate(string $content): array
    {
        $errors = [];
        $this->validateBlocks($content, 0, $errors);

        return [
            'valid' => $errors === [],
            'errors' => array_values(array_unique($errors)),
        ];
    }

    /**
     * @param  list<string>  $errors
     */
    private function validateBlocks(string $content, int $depth, array &$errors): void
    {
        $offset = 0;

        while (($start = strpos($content, '{{#if', $offset)) !== false) {
            if ($depth >= 2) {
                $errors[] = 'Dynamic content nesting deeper than 2 levels is not supported.';

                return;
            }

            $conditionEnd = strpos($content, '}}', $start);
            if ($conditionEnd === false) {
                $errors[] = 'Dynamic content block is missing a condition terminator.';

                return;
            }

            $condition = trim(substr($content, $start + 5, $conditionEnd - ($start + 5)));
            if (! $this->isValidCondition($condition)) {
                $errors[] = sprintf('Invalid dynamic condition: %s', $condition);
            }

            $block = $this->extractBlock($content, $start);
            if ($block === null) {
                $errors[] = 'Dynamic content block is missing a closing {{/if}}.';

                return;
            }

            $this->validateBlocks($block['truthy'], $depth + 1, $errors);

            if ($block['falsy'] !== null) {
                $this->validateBlocks($block['falsy'], $depth + 1, $errors);
            }

            $offset = $block['end'];
        }
    }

    private function isValidCondition(string $condition): bool
    {
        if (! preg_match('/^(contact\.[a-zA-Z0-9_]+|client\.[a-zA-Z0-9_]+|email_score)\s*(==|!=|>=|<=|>|<)\s*(.+)$/', $condition, $matches)) {
            return false;
        }

        $field = $matches[1];
        $value = trim($matches[3]);

        if (! $this->isAllowedField($field)) {
            return false;
        }

        return is_numeric($value)
            || preg_match('/^".*"$/', $value) === 1
            || preg_match("/^'.*'$/", $value) === 1;
    }

    private function isAllowedField(string $field): bool
    {
        if ($field === 'email_score') {
            return true;
        }

        if (str_starts_with($field, 'contact.')) {
            return in_array(substr($field, 8), ['first_name', 'last_name', 'email', 'phone', 'position'], true);
        }

        if (str_starts_with($field, 'client.')) {
            return in_array(substr($field, 7), ['name', 'email', 'phone', 'city', 'country', 'address', 'zip_code', 'industry', 'department', 'reference'], true);
        }

        return false;
    }

    /**
     * @return array{truthy:string, falsy:string|null, end:int}|null
     */
    private function extractBlock(string $content, int $start): ?array
    {
        $conditionEnd = strpos($content, '}}', $start);
        if ($conditionEnd === false) {
            return null;
        }

        $cursor = $conditionEnd + 2;
        $depth = 1;
        $elsePosition = null;

        while ($depth > 0) {
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

            if ($type === 'end') {
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

        return null;
    }
}
