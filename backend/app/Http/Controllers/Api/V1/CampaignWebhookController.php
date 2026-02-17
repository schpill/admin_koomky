<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CampaignRecipient;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignWebhookController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['required', 'string'],
            'recipient_id' => ['required', 'uuid'],
            'failure_reason' => ['nullable', 'string'],
        ]);

        $event = strtolower((string) $validated['event']);

        if (! in_array($event, ['bounce', 'complaint', 'delivery'], true)) {
            return $this->error('Invalid webhook event', 400);
        }

        $recipient = CampaignRecipient::query()->findOrFail((string) $validated['recipient_id']);

        if ($event === 'bounce') {
            $recipient->update([
                'status' => 'bounced',
                'bounced_at' => now(),
                'failure_reason' => $validated['failure_reason'] ?? null,
            ]);

            return $this->success(null, 'Bounce processed');
        }

        if ($event === 'complaint') {
            $recipient->update([
                'status' => 'unsubscribed',
                'failure_reason' => $validated['failure_reason'] ?? 'complaint',
            ]);

            $contact = $recipient->contact;
            if ($contact !== null && $contact->email_unsubscribed_at === null) {
                $contact->update(['email_unsubscribed_at' => now()]);
            }

            return $this->success(null, 'Complaint processed');
        }

        $recipient->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        return $this->success(null, 'Delivery processed');
    }
}
