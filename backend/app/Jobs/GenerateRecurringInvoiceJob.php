<?php

namespace App\Jobs;

use App\Models\RecurringInvoiceProfile;
use App\Notifications\RecurringInvoiceGeneratedNotification;
use App\Services\RecurringInvoiceGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateRecurringInvoiceJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $profileId) {}

    public function handle(RecurringInvoiceGeneratorService $generatorService): void
    {
        $profile = RecurringInvoiceProfile::query()->with('user')->find($this->profileId);

        if (! $profile || $profile->status !== 'active' || $profile->next_due_date->isFuture()) {
            return;
        }

        $invoice = $generatorService->generate($profile);

        if ($profile->auto_send) {
            SendInvoiceJob::dispatch($invoice->id);
        }

        if ($profile->user) {
            $profile->user->notify(new RecurringInvoiceGeneratedNotification($profile, $invoice));
        }
    }
}
