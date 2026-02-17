<?php

namespace App\Console\Commands;

use App\Jobs\GenerateRecurringInvoiceJob;
use App\Models\RecurringInvoiceProfile;
use Illuminate\Console\Command;

class GenerateRecurringInvoicesCommand extends Command
{
    protected $signature = 'invoices:generate-recurring';

    protected $description = 'Generate invoices from active recurring profiles that are due';

    public function handle(): int
    {
        $profileIds = RecurringInvoiceProfile::query()
            ->active()
            ->due()
            ->pluck('id')
            ->all();

        foreach ($profileIds as $profileId) {
            GenerateRecurringInvoiceJob::dispatch($profileId);
        }

        $this->info('Recurring invoice jobs dispatched: '.count($profileIds));

        return self::SUCCESS;
    }
}
