<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

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
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Unable to open XLSX file.');
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();
            throw new RuntimeException('XLSX worksheet not found.');
        }

        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        $sharedStrings = [];
        if (is_string($sharedStringsXml)) {
            $xml = simplexml_load_string($sharedStringsXml);
            if ($xml instanceof SimpleXMLElement) {
                foreach ($xml->si as $item) {
                    $text = '';
                    if (isset($item->t)) {
                        $text = (string) $item->t;
                    } else {
                        foreach ($item->r as $run) {
                            $text .= (string) $run->t;
                        }
                    }
                    $sharedStrings[] = $this->normalizeString($text);
                }
            }
        }

        $xml = simplexml_load_string($sheetXml);
        if (! $xml instanceof SimpleXMLElement || ! isset($xml->sheetData)) {
            throw new RuntimeException('Invalid XLSX content.');
        }

        $table = [];
        foreach ($xml->sheetData->row as $row) {
            $line = [];
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                preg_match('/([A-Z]+)(\d+)/', $ref, $matches);
                $index = $this->columnToIndex($matches[1] ?? 'A');

                $value = null;
                if (isset($cell->v)) {
                    $raw = (string) $cell->v;
                    if ((string) $cell['t'] === 's') {
                        $value = $sharedStrings[(int) $raw] ?? '';
                    } else {
                        $value = $raw;
                    }
                }

                $line[$index] = $this->normalizeString((string) $value);
            }

            if ($line !== []) {
                ksort($line);
                $table[] = $line;
            }
        }

        if ($table === []) {
            throw new RuntimeException('The import file is empty.');
        }

        $headerRow = array_shift($table);

        $maxIndex = max(array_keys($headerRow));
        $headers = [];
        for ($i = 0; $i <= $maxIndex; $i++) {
            $headers[] = $headerRow[$i] ?? '';
        }

        $rows = [];
        foreach ($table as $line) {
            $mapped = [];
            foreach ($headers as $index => $header) {
                $value = $line[$index] ?? null;
                $mapped[$header] = $value === '' ? null : $value;
            }

            $hasValues = count(array_filter($mapped, fn ($value) => $value !== null)) > 0;
            if (! $hasValues) {
                continue;
            }

            $rows[] = $mapped;
            if (count($rows) > self::MAX_ROWS) {
                throw new RuntimeException('Import limit exceeded (max 10000 rows).');
            }
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
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

    private function columnToIndex(string $column): int
    {
        $index = 0;
        $chars = str_split($column);

        foreach ($chars as $char) {
            $index = ($index * 26) + (ord($char) - 64);
        }

        return max(0, $index - 1);
    }
}
