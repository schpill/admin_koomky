<?php

namespace App\Jobs;

use App\Mail\ReminderMail;
use App\Models\InvoiceReminderSchedule;
use App\Models\PortalAccessToken;
use App\Models\ReminderDelivery;
use App\Services\ActivityService;
use App\Services\ReminderDispatchService;
use App\Services\WebhookDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(public readonly string $scheduleId)
    {
        $this->onQueue('reminders');
    }

    public function handle(ReminderDispatchService $dispatchService, WebhookDispatchService $webhookDispatchService): void
    {
        $schedule = InvoiceReminderSchedule::query()
            ->with(['invoice.client', 'nextStep'])
            ->find($this->scheduleId);

        if (! $schedule || $schedule->completed_at !== null || $schedule->is_paused) {
            return;
        }

        $step = $schedule->nextStep;
        $invoice = $schedule->invoice;
        $client = $invoice?->client;

        if (! $step || ! $invoice || ! $client || ! $client->email) {
            return;
        }

        $payLink = PortalAccessToken::query()
            ->where('client_id', $client->id)
            ->active()
            ->notExpired()
            ->latest('created_at')
            ->first();

        $resolvedPayLink = $payLink
            ? rtrim((string) config('app.url'), '/').'/portal/auth/verify/'.$payLink->token
            : null;

        Mail::to($client->email)->send(new ReminderMail($invoice, $step, $resolvedPayLink));

        ReminderDelivery::query()->updateOrCreate(
            [
                'invoice_id' => $invoice->id,
                'reminder_step_id' => $step->id,
            ],
            [
                'user_id' => $invoice->user_id,
                'sent_at' => now(),
                'status' => 'sent',
                'error_message' => null,
            ]
        );

        $refreshedSchedule = $schedule->fresh();
        if ($refreshedSchedule) {
            $dispatchService->advanceStep($refreshedSchedule);
        }

        $webhookDispatchService->dispatch('invoice.reminder_sent', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->number,
            'client_id' => $invoice->client_id,
            'step_number' => $step->step_number,
            'delay_days' => $step->delay_days,
            'sent_at' => now()->toIso8601String(),
        ], $invoice->user_id);

        ActivityService::log($client, "Reminder sent: {$invoice->number}", [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->number,
            'step_number' => $step->step_number,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $schedule = InvoiceReminderSchedule::query()->with(['invoice', 'nextStep'])->find($this->scheduleId);
        if (! $schedule || ! $schedule->invoice || ! $schedule->nextStep) {
            return;
        }

        ReminderDelivery::query()->updateOrCreate(
            [
                'invoice_id' => $schedule->invoice->id,
                'reminder_step_id' => $schedule->nextStep->id,
            ],
            [
                'user_id' => $schedule->invoice->user_id,
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]
        );
    }
}
