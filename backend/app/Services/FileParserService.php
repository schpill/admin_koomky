<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class FileParserService
{
    private const MAX_ROWS = 10000;

    /**
     * @return array{headers:list<string>,rows:list<array<string,string|null>>}
     */
    public function parse(string $path, string $extension): array
    {
        $normalizedExtension = strtolower($extension);

        return match ($normalizedExtension) {
            'csv' => $this->parseCsv($path),
            'xlsx', 'xls' => $this->parseXlsx($path),
            default => throw new RuntimeException('Unsupported file type for import.'),
        };
    }

    /**
     * @return array{headers:list<string>,rows:list<array<string,string|null>>}
     */
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to open CSV file.');
        }

        $firstLine = fgets($handle);
        if ($firstLine === false || trim($firstLine) === '') {
            fclose($handle);
            throw new RuntimeException('The import file is empty.');
        }

        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        rewind($handle);

        $headers = fgetcsv($handle, 0, $delimiter, '"', '\\');
        if ($headers === false) {
            fclose($handle);
            throw new RuntimeException('Invalid CSV header row.');
        }

        $normalizedHeaders = array_map(fn ($header): string => $this->normalizeString((string) $header), $headers);
        if (count(array_filter($normalizedHeaders, fn (string $h): bool => $h !== '')) === 0) {
            fclose($handle);
            throw new RuntimeException('Invalid CSV headers.');
        }

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $row = array_pad($row, count($normalizedHeaders), null);
            $mapped = [];
            foreach ($normalizedHeaders as $index => $header) {
                $mapped[$header] = isset($row[$index]) ? $this->normalizeString((string) $row[$index]) : null;
                if ($mapped[$header] === '') {
                    $mapped[$header] = null;
                }
            }

            $hasValues = count(array_filter($mapped, fn ($value) => $value !== null && $value !== '')) > 0;
            if (! $hasValues) {
                continue;
            }

            $rows[] = $mapped;
            if (count($rows) > self::MAX_ROWS) {
                fclose($handle);
                throw new RuntimeException('Import limit exceeded (max 10000 rows).');
            }
        }

        fclose($handle);

        return [
            'headers' => $normalizedHeaders,
            'rows' => $rows,
        ];
    }

    /**
     * @return array{headers:list<string>,rows:list<array<string,string|null>>}
     */
    private function parseXlsx(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray(null, true, true, false);

        if ($rows === []) {
            throw new RuntimeException('The import file is empty.');
        }

        $headerRow = array_shift($rows);
        if (! is_array($headerRow)) {
            throw new RuntimeException('Invalid XLSX headers.');
        }

        $headers = array_values(array_map(
            fn (mixed $value): string => $this->normalizeString((string) ($value ?? '')),
            $headerRow
        ));

        if (count(array_filter($headers, fn (string $h): bool => $h !== '')) === 0) {
            throw new RuntimeException('Invalid XLSX headers.');
        }

        $normalizedRows = [];
        foreach ($rows as $line) {
            if (! is_array($line)) {
                continue;
            }

            $mapped = [];
            foreach ($headers as $index => $header) {
                $rawValue = $line[$index] ?? null;
                $value = $rawValue === null ? null : $this->normalizeString((string) $rawValue);
                $mapped[$header] = $value === '' ? null : $value;
            }

            $hasValues = count(array_filter($mapped, fn ($value) => $value !== null)) > 0;
            if (! $hasValues) {
                continue;
            }

            $normalizedRows[] = $mapped;
            if (count($normalizedRows) > self::MAX_ROWS) {
                throw new RuntimeException('Import limit exceeded (max 10000 rows).');
            }
        }

        return [
            'headers' => $headers,
            'rows' => $normalizedRows,
        ];
    }

    private function normalizeString(string $value): string
    {
        $encoding = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding !== false && $encoding !== 'UTF-8') {
            $value = mb_convert_encoding($value, 'UTF-8', $encoding);
        }

        return trim((string) $value);
    }
}
