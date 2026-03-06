<?php

namespace App\Console\Commands;

use App\Models\DripEnrollment;
use Illuminate\Console\Command;

class PruneDripEnrollmentsCommand extends Command
{
    protected $signature = 'drip-enrollments:prune';

    protected $description = 'Remove old completed or cancelled drip enrollments';

    public function handle(): int
    {
        $deleted = DripEnrollment::query()
            ->whereIn('status', ['completed', 'cancelled'])
            ->where('updated_at', '<=', now()->subDays(90))
            ->delete();

        $this->info("Pruned {$deleted} drip enrollments.");

        return self::SUCCESS;
    }
}
