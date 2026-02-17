<?php

namespace App\Support;

class InputSanitizer
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    public static function sanitizeFields(array $payload, array $fields): array
    {
        foreach ($fields as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            $payload[$field] = self::sanitizeValue($payload[$field]);
        }

        return $payload;
    }

    private static function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(
                fn (mixed $child): mixed => self::sanitizeValue($child),
                $value
            );
        }

        if (! is_string($value)) {
            return $value;
        }

        // Remove executable payloads first, then strip remaining tags.
        $withoutScripts = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $value);
        $withoutStyles = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', (string) $withoutScripts);
        $stripped = strip_tags((string) $withoutStyles);
        $collapsedWhitespace = preg_replace('/\s+/', ' ', $stripped);

        return trim((string) $collapsedWhitespace);
    }
}
