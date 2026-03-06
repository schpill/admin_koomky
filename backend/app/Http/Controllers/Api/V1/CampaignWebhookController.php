<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\RetryBouncedEmailJob;
use App\Models\CampaignRecipient;
use App\Models\User;
use App\Services\SuppressionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CampaignWebhookController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SuppressionService $suppressionService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['required', 'string'],
            'recipient_id' => ['required', 'uuid'],
            'failure_reason' => ['nullable', 'string'],
            'bounce_type' => ['nullable', Rule::in(['hard', 'soft'])],
        ]);

        $event = strtolower((string) $validated['event']);

        if (! in_array($event, ['bounce', 'complaint', 'delivery'], true)) {
            return $this->error('Invalid webhook event', 400);
        }

        $recipient = CampaignRecipient::query()->findOrFail((string) $validated['recipient_id']);

        if ($event === 'bounce') {
            $bounceType = (string) ($validated['bounce_type'] ?? 'hard');
            $bounceCount = $bounceType === 'soft'
                ? ((int) $recipient->bounce_count) + 1
                : max(1, (int) $recipient->bounce_count);

            $recipient->update([
                'status' => 'bounced',
                'bounced_at' => now(),
                'failure_reason' => $validated['failure_reason'] ?? null,
                'bounce_type' => $bounceType,
                'bounce_count' => $bounceCount,
            ]);

            if ($bounceType === 'soft' && $bounceCount < 3) {
                RetryBouncedEmailJob::dispatch($recipient->id);
            }

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

            $campaignUser = $recipient->campaign?->user;
            if ($campaignUser instanceof User && is_string($recipient->email) && $recipient->email !== '') {
                $this->suppressionService->suppress($campaignUser, $recipient->email, 'unsubscribed', $recipient->campaign_id);
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
