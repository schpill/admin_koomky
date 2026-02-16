<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class MarkOverdueInvoicesCommand extends Command
{
    protected $signature = 'invoices:mark-overdue';

    protected $description = 'Mark sent/viewed invoices as overdue when due date has passed';

    public function handle(): int
    {
        $updated = 0;

        $invoices = Invoice::query()
            ->whereIn('status', ['sent', 'viewed'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        foreach ($invoices as $invoice) {
            /** @var Invoice $invoice */
            $invoice->update(['status' => 'overdue']);
            $updated++;
        }

        $this->info("Overdue invoices marked: {$updated}");

        return self::SUCCESS;
    }
}
