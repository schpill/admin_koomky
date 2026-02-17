<?php

namespace App\Services\ExchangeRates;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use SimpleXMLElement;

class EcbExchangeRatesDriver implements ExchangeRateDriver
{
    /**
     * @return array<string, float>
     */
    public function fetchRates(string $baseCurrency): array
    {
        $response = Http::timeout(15)->get('https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml');

        if (! $response->successful()) {
            throw new RuntimeException('Failed to fetch exchange rates from ECB');
        }

        $xml = @simplexml_load_string($response->body());
        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException('Invalid ECB XML payload');
        }

        $namespaces = $xml->getDocNamespaces(true);
        $defaultNamespace = $namespaces[''] ?? null;
        if (! is_string($defaultNamespace)) {
            throw new RuntimeException('Missing ECB namespace');
        }

        $nodes = $xml->children($defaultNamespace)->Cube->Cube->Cube;
        $eurRates = ['EUR' => 1.0];

        foreach ($nodes as $node) {
            $attributes = $node->attributes();
            if (! isset($attributes['currency'], $attributes['rate'])) {
                continue;
            }

            $eurRates[strtoupper((string) $attributes['currency'])] = (float) $attributes['rate'];
        }

        $base = strtoupper($baseCurrency);
        $baseRate = $eurRates[$base] ?? null;

        if ($baseRate === null || $baseRate <= 0) {
            throw new RuntimeException('ECB does not provide requested base currency: '.$base);
        }

        $normalized = [];
        foreach ($eurRates as $target => $eurToTarget) {
            if ($target === $base) {
                continue;
            }

            $normalized[$target] = (float) round($eurToTarget / $baseRate, 6);
        }

        return $normalized;
    }

    public function source(): string
    {
        return 'ecb';
    }
}
