<?php

namespace App\Console\Commands;

use App\Models\Quote;
use Illuminate\Console\Command;

class MarkExpiredQuotesCommand extends Command
{
    protected $signature = 'quotes:mark-expired';

    protected $description = 'Mark sent quotes as expired when validity period has passed';

    public function handle(): int
    {
        $updated = 0;

        $quotes = Quote::query()
            ->where('status', 'sent')
            ->whereDate('valid_until', '<', now()->toDateString())
            ->get();

        foreach ($quotes as $quote) {
            /** @var Quote $quote */
            $quote->update(['status' => 'expired']);
            $updated++;
        }

        $this->info("Expired quotes marked: {$updated}");

        return self::SUCCESS;
    }
}
