<?php

namespace App\Jobs;

use App\Models\CampaignRecipient;
use App\Services\PersonalizationService;
use App\Services\Sms\SmsProviderManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendCampaignSmsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $recipientId) {}

    public function handle(PersonalizationService $personalizationService, SmsProviderManager $smsProviderManager): void
    {
        $recipient = CampaignRecipient::query()
            ->with(['campaign.user', 'contact.client'])
            ->find($this->recipientId);

        if (! $recipient || ! $recipient->campaign || ! is_string($recipient->phone) || $recipient->phone === '') {
            return;
        }

        $campaign = $recipient->campaign;
        $user = $campaign->user;
        if ($user === null) {
            return;
        }
        $baseMessage = $campaign->content;

        if ($recipient->contact !== null) {
            $baseMessage = $personalizationService->render($baseMessage, $recipient->contact);
        }

        $message = trim($baseMessage).' Reply STOP to unsubscribe';

        try {
            $result = $smsProviderManager->send((array) ($user->sms_settings ?? []), $recipient->phone, $message);

            $recipient->update([
                'status' => 'sent',
                'sent_at' => now(),
                'metadata' => [
                    'message_id' => $result['message_id'],
                    'provider' => $result['provider'],
                ],
            ]);
        } catch (\Throwable $exception) {
            $recipient->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $exception->getMessage(),
            ]);
        }
    }
}
