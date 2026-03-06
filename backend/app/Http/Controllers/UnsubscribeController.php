<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Services\ContactScoreService;
use App\Services\SuppressionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnsubscribeController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SuppressionService $suppressionService,
        private readonly ContactScoreService $contactScoreService,
    ) {}

    public function __invoke(Request $request, Contact $contact): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return $this->error('Invalid or expired unsubscribe link', 403);
        }

        if ($contact->email_unsubscribed_at === null) {
            $contact->forceFill([
                'email_unsubscribed_at' => now(),
            ])->save();

            $this->contactScoreService->recordEvent($contact, 'email_unsubscribed');
        }

        $recipient = CampaignRecipient::query()
            ->with('campaign.user')
            ->where('contact_id', $contact->id)
            ->latest('created_at')
            ->first();

        if ($recipient !== null && $recipient->campaign?->user !== null && is_string($contact->email) && $contact->email !== '') {
            $this->suppressionService->suppress(
                $recipient->campaign->user,
                $contact->email,
                'unsubscribed',
                $recipient->campaign_id
            );
        }

        return $this->success([
            'contact_id' => $contact->id,
            'email_unsubscribed_at' => $contact->email_unsubscribed_at,
        ], 'Unsubscribe processed successfully');
    }
}
