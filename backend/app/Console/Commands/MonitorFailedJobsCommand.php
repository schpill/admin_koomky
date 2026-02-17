<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitorFailedJobsCommand extends Command
{
    protected $signature = 'queue:monitor-failures {--threshold=}';

    protected $description = 'Monitor failed jobs over the last hour and alert when threshold is exceeded';

    public function handle(): int
    {
        $configuredThreshold = (int) config('performance.failed_jobs_alert_threshold', 10);
        $optionThreshold = $this->option('threshold');
        $threshold = is_numeric($optionThreshold) ? (int) $optionThreshold : $configuredThreshold;

        $count = DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subHour())
            ->count();

        $this->line(sprintf('Failed jobs in the last hour: %d', $count));

        $context = [
            'count' => $count,
            'threshold' => $threshold,
            'window_minutes' => 60,
        ];

        if ($count > $threshold) {
            Log::warning('failed_jobs_threshold_exceeded', $context);

            return self::FAILURE;
        }

        Log::info('failed_jobs_within_threshold', $context);

        return self::SUCCESS;
    }
}
