<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsWebhookController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['required', 'string', 'in:delivered,failed,opt_out'],
            'recipient_id' => ['required', 'uuid'],
            'failure_reason' => ['nullable', 'string'],
            'keyword' => ['nullable', 'string'],
        ]);

        $recipient = CampaignRecipient::query()->findOrFail((string) $validated['recipient_id']);
        $event = (string) $validated['event'];

        if ($event === 'delivered') {
            $recipient->update([
                'status' => 'delivered',
                'delivered_at' => now(),
            ]);

            return $this->success(null, 'SMS delivery processed');
        }

        if ($event === 'failed') {
            $recipient->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $validated['failure_reason'] ?? null,
            ]);

            return $this->success(null, 'SMS failure processed');
        }

        $recipient->update([
            'status' => 'unsubscribed',
        ]);

        $contact = $recipient->contact;
        if ($contact !== null && strtoupper((string) ($validated['keyword'] ?? 'STOP')) === 'STOP') {
            $contact->update(['sms_opted_out_at' => now()]);
        }

        return $this->success(null, 'SMS opt-out processed');
    }
}
