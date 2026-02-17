<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PrometheusMetricsService
{
    /** @var array<string, array<string, array{labels: array<string, string>, value: float}>> */
    private static array $counters = [];

    /** @var array<string, array<string, array{labels: array<string, string>, value: float}>> */
    private static array $gauges = [];

    /**
     * @var array<string, array<string, array{
     *   labels: array<string, string>,
     *   count: int,
     *   sum: float,
     *   buckets: array<int|string, int>
     * }>>
     */
    private static array $histograms = [];

    /** @var list<float> */
    private array $defaultBuckets = [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10];

    public function reset(): void
    {
        self::$counters = [];
        self::$gauges = [];
        self::$histograms = [];
    }

    /**
     * @param  array<string, string>  $labels
     */
    public function incrementCounter(string $name, array $labels = [], float $value = 1): void
    {
        $key = $this->labelsKey($labels);
        $current = self::$counters[$name][$key]['value'] ?? 0.0;

        self::$counters[$name][$key] = [
            'labels' => $labels,
            'value' => $current + $value,
        ];
    }

    /**
     * @param  array<string, string>  $labels
     */
    public function setGauge(string $name, float $value, array $labels = []): void
    {
        $key = $this->labelsKey($labels);
        self::$gauges[$name][$key] = [
            'labels' => $labels,
            'value' => $value,
        ];
    }

    /**
     * @param  array<string, string>  $labels
     */
    public function observeHistogram(string $name, float $value, array $labels = []): void
    {
        $key = $this->labelsKey($labels);

        if (! isset(self::$histograms[$name][$key])) {
            /** @var array<string, int> $bucketState */
            $bucketState = [];
            foreach ($this->defaultBuckets as $bucket) {
                $bucketKey = (string) $bucket;
                $bucketState[$bucketKey] = 0;
            }
            $bucketState['+Inf'] = 0;

            self::$histograms[$name][$key] = [
                'labels' => $labels,
                'count' => 0,
                'sum' => 0.0,
                'buckets' => $bucketState,
            ];
        }

        self::$histograms[$name][$key]['count']++;
        self::$histograms[$name][$key]['sum'] += $value;

        foreach ($this->defaultBuckets as $bucket) {
            if ($value <= $bucket) {
                $bucketKey = (string) $bucket;
                self::$histograms[$name][$key]['buckets'][$bucketKey]++;
            }
        }
        self::$histograms[$name][$key]['buckets']['+Inf']++;
    }

    public function render(): string
    {
        $this->collectApplicationMetrics();

        $lines = [];

        foreach (self::$counters as $name => $series) {
            $lines[] = "# TYPE {$name} counter";
            foreach ($series as $sample) {
                $lines[] = $this->sampleLine($name, $sample['labels'], $sample['value']);
            }
        }

        foreach (self::$gauges as $name => $series) {
            $lines[] = "# TYPE {$name} gauge";
            foreach ($series as $sample) {
                $lines[] = $this->sampleLine($name, $sample['labels'], $sample['value']);
            }
        }

        foreach (self::$histograms as $name => $series) {
            $lines[] = "# TYPE {$name} histogram";
            foreach ($series as $sample) {
                $labels = $sample['labels'];
                foreach ($sample['buckets'] as $bucket => $count) {
                    $bucketLabels = [...$labels, 'le' => (string) $bucket];
                    $lines[] = $this->sampleLine($name.'_bucket', $bucketLabels, $count);
                }
                $lines[] = $this->sampleLine($name.'_sum', $labels, $sample['sum']);
                $lines[] = $this->sampleLine($name.'_count', $labels, $sample['count']);
            }
        }

        return implode("\n", $lines)."\n";
    }

    private function collectApplicationMetrics(): void
    {
        $this->setGauge('koomky_active_users_total', (float) User::query()->count());
        $this->setGauge('koomky_invoices_generated_total', (float) Invoice::query()->count());
        $this->setGauge(
            'koomky_campaigns_sent_total',
            (float) Campaign::query()->whereIn('status', ['sending', 'completed', 'sent'])->count()
        );

        if (Schema::hasTable('jobs')) {
            $queueRows = DB::table('jobs')
                ->select('queue', DB::raw('COUNT(*) as waiting'))
                ->groupBy('queue')
                ->get();

            if ($queueRows->isEmpty()) {
                $this->setGauge('koomky_queue_jobs_waiting', 0, ['queue' => 'default']);
            } else {
                foreach ($queueRows as $row) {
                    $this->setGauge('koomky_queue_jobs_waiting', (float) $row->waiting, ['queue' => (string) $row->queue]);
                }
            }
        } else {
            $this->setGauge('koomky_queue_jobs_waiting', 0, ['queue' => 'default']);
        }

        $processed = 0.0;
        if (Schema::hasTable('failed_jobs')) {
            $processed += (float) DB::table('failed_jobs')->count();
            $this->setGauge('koomky_queue_jobs_processed_total', $processed, [
                'queue' => 'default',
                'status' => 'failed',
            ]);
        } else {
            $this->setGauge('koomky_queue_jobs_processed_total', 0, [
                'queue' => 'default',
                'status' => 'failed',
            ]);
        }

        $emailsSent = Schema::hasTable('campaign_recipients')
            ? (float) CampaignRecipient::query()->whereIn('status', ['sent', 'delivered'])->count()
            : 0.0;

        $this->setGauge('koomky_emails_sent_total', $emailsSent, ['type' => 'campaign']);
        $this->setGauge('koomky_emails_sent_total', 0, ['type' => 'invoice']);
        $this->setGauge('koomky_emails_sent_total', 0, ['type' => 'notification']);
    }

    /**
     * @param  array<string, string>  $labels
     */
    private function sampleLine(string $metric, array $labels, float|int $value): string
    {
        $labelPart = '';
        if ($labels !== []) {
            $segments = [];
            foreach ($labels as $key => $labelValue) {
                $escapedValue = str_replace(['\\', '"', "\n"], ['\\\\', '\\"', '\\n'], (string) $labelValue);
                $segments[] = "{$key}=\"{$escapedValue}\"";
            }

            $labelPart = '{'.implode(',', $segments).'}';
        }

        return "{$metric}{$labelPart} {$value}";
    }

    /**
     * @param  array<string, string>  $labels
     */
    private function labelsKey(array $labels): string
    {
        ksort($labels);

        return md5(json_encode($labels, JSON_THROW_ON_ERROR));
    }
}
