<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Services\WebhookDispatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebhookDispatchJob implements ShouldQueue
{
    use Queueable;

    /**
     * Maximum number of retry attempts.
     */
    public int $tries = 5;

    /**
     * Exponential backoff delays in seconds: 10s, 1min, 5min, 15min, 30min.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 60, 300, 900, 1800];

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $deliveryId) {}

    /**
     * Execute the job.
     */
    public function handle(WebhookDispatchService $dispatchService): void
    {
        $delivery = WebhookDelivery::query()
            ->with('endpoint')
            ->find($this->deliveryId);

        if (! $delivery) {
            Log::warning('webhook_dispatch_delivery_not_found', [
                'delivery_id' => $this->deliveryId,
            ]);

            return;
        }

        // Skip if already delivered
        if ($delivery->delivered_at !== null) {
            Log::info('webhook_dispatch_already_delivered', [
                'delivery_id' => $this->deliveryId,
            ]);

            return;
        }

        // Check if endpoint is still active
        $endpoint = $delivery->endpoint;
        if (! $endpoint->is_active) {
            Log::warning('webhook_dispatch_endpoint_inactive', [
                'delivery_id' => $this->deliveryId,
                'endpoint_id' => $delivery->webhook_endpoint_id,
            ]);

            return;
        }

        try {
            $result = $dispatchService->retry($delivery);

            if ($result->delivered_at !== null) {
                Log::info('webhook_dispatch_successful', [
                    'delivery_id' => $this->deliveryId,
                    'endpoint_id' => $delivery->webhook_endpoint_id,
                    'event' => $delivery->event,
                    'attempt' => $delivery->attempt_count,
                ]);
            } else {
                Log::warning('webhook_dispatch_failed_attempt', [
                    'delivery_id' => $this->deliveryId,
                    'endpoint_id' => $delivery->webhook_endpoint_id,
                    'event' => $delivery->event,
                    'attempt' => $delivery->attempt_count,
                    'response_status' => $delivery->response_status,
                ]);

                // If this was the last attempt and still failing, mark as permanently failed
                if (! $delivery->canRetry()) {
                    Log::error('webhook_dispatch_permanently_failed', [
                        'delivery_id' => $this->deliveryId,
                        'endpoint_id' => $delivery->webhook_endpoint_id,
                        'event' => $delivery->event,
                        'total_attempts' => $delivery->attempt_count,
                    ]);
                }
            }
        } catch (Throwable $exception) {
            Log::error('webhook_dispatch_exception', [
                'delivery_id' => $this->deliveryId,
                'endpoint_id' => $delivery->webhook_endpoint_id,
                'event' => $delivery->event,
                'attempt' => $delivery->attempt_count,
                'error' => $exception->getMessage(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $exception;
        }
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        // Give up after 2 hours total
        return now()->addHours(2);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('webhook_dispatch_job_failed', [
            'delivery_id' => $this->deliveryId,
            'error' => $exception?->getMessage(),
        ]);

        $delivery = WebhookDelivery::query()->find($this->deliveryId);

        if ($delivery) {
            $delivery->failed_at = now();
            $delivery->next_retry_at = null;
            $delivery->save();
        }
    }
}
