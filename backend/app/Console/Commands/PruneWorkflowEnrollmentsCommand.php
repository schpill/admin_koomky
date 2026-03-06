<?php

namespace App\Console\Commands;

use App\Models\WorkflowEnrollment;
use Illuminate\Console\Command;

class PruneWorkflowEnrollmentsCommand extends Command
{
    protected $signature = 'workflow-enrollments:prune';

    protected $description = 'Remove old completed or cancelled workflow enrollments';

    public function handle(): int
    {
        $deleted = WorkflowEnrollment::query()
            ->whereIn('status', ['completed', 'cancelled'])
            ->where('updated_at', '<=', now()->subDays(90))
            ->delete();

        $this->info("Pruned {$deleted} workflow enrollments.");

        return self::SUCCESS;
    }
}
