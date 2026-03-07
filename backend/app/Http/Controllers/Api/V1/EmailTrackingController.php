<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CampaignLinkClick;
use App\Models\CampaignRecipient;
use App\Services\ContactScoreService;
use App\Services\EmailTrackingTokenService;
use App\Services\WebhookDispatchService;
use App\Services\WorkflowTriggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class EmailTrackingController extends Controller
{
    public function __construct(
        private readonly EmailTrackingTokenService $tokenService,
        private readonly ContactScoreService $contactScoreService,
        private readonly WebhookDispatchService $webhookDispatchService,
        private readonly WorkflowTriggerService $workflowTriggerService,
    ) {}

    public function open(string $token): Response
    {
        $recipient = $this->resolveRecipient($token);
        if ($recipient === null) {
            abort(404);
        }

        if ($recipient->opened_at === null) {
            $openedAt = now();
            $recipient->update([
                'opened_at' => $openedAt,
                'status' => in_array($recipient->status, ['sent', 'delivered'], true) ? 'opened' : $recipient->status,
            ]);

            if ($recipient->variant_id !== null) {
                $recipient->variant()->increment('open_count');
            }

            if ($recipient->contact !== null) {
                $this->contactScoreService->recordEvent($recipient->contact, 'email_opened', $recipient->campaign);
                $contact = $recipient->contact->fresh();
                if ($contact !== null) {
                    $this->workflowTriggerService->evaluateTriggers('email_opened', $contact, [
                        'campaign_id' => $recipient->campaign_id,
                    ]);
                }
            }

            if ($recipient->campaign !== null) {
                $this->webhookDispatchService->dispatch('email.opened', [
                    'campaign_id' => $recipient->campaign_id,
                    'contact_id' => $recipient->contact_id,
                    'opened_at' => $openedAt->toIso8601String(),
                ], $recipient->campaign->user_id);
            }
        }

        $pixel = base64_decode('R0lGODlhAQABAIABAP///wAAACwAAAAAAQABAAACAkQBADs=', true) ?: '';

        return response($pixel, 200, ['Content-Type' => 'image/gif']);
    }

    public function click(Request $request, string $token): RedirectResponse
    {
        $recipient = $this->resolveRecipient($token);
        if ($recipient === null) {
            abort(404);
        }

        $url = (string) $request->query('url', '/');
        $clickedAt = now();
        $isFirstClickForUrl = ! CampaignLinkClick::query()
            ->where('recipient_id', $recipient->id)
            ->where('url', $url)
            ->exists();

        CampaignLinkClick::query()->create([
            'user_id' => $recipient->campaign?->user_id,
            'campaign_id' => $recipient->campaign_id,
            'recipient_id' => $recipient->id,
            'contact_id' => $recipient->contact_id,
            'url' => $url,
            'clicked_at' => $clickedAt,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($recipient->clicked_at === null) {
            $recipient->update([
                'clicked_at' => $clickedAt,
                'status' => 'clicked',
            ]);

            if ($recipient->variant_id !== null) {
                $recipient->variant()->increment('click_count');
            }

            if ($recipient->contact !== null && $isFirstClickForUrl) {
                if ($recipient->contact->timezone === null) {
                    $timezone = $this->resolveTimezoneFromIp($request->ip());
                    if ($timezone !== null) {
                        $recipient->contact->forceFill(['timezone' => $timezone])->save();
                    }
                }

                $this->contactScoreService->recordEvent($recipient->contact, 'email_clicked', $recipient->campaign);
                $contact = $recipient->contact->fresh();
                if ($contact !== null) {
                    $this->workflowTriggerService->evaluateTriggers('email_clicked', $contact, [
                        'campaign_id' => $recipient->campaign_id,
                        'url' => $url,
                    ]);
                }
            }
        }

        if ($recipient->campaign !== null) {
            $this->webhookDispatchService->dispatch('email.clicked', [
                'campaign_id' => $recipient->campaign_id,
                'contact_id' => $recipient->contact_id,
                'url' => $url,
                'clicked_at' => $clickedAt->toIso8601String(),
            ], $recipient->campaign->user_id);
        }

        return redirect()->away($url);
    }

    private function resolveRecipient(string $token): ?CampaignRecipient
    {
        $recipientId = $this->tokenService->decode($token);
        if ($recipientId === null) {
            return null;
        }

        return CampaignRecipient::query()->find($recipientId);
    }

    private function resolveTimezoneFromIp(?string $ipAddress): ?string
    {
        if (! is_string($ipAddress) || $ipAddress === '') {
            return null;
        }

        $configuredTimezones = config('services.geoip.testing_timezones', []);
        if (is_array($configuredTimezones)) {
            $configuredTimezone = $configuredTimezones[$ipAddress] ?? null;
            if (is_string($configuredTimezone) && $configuredTimezone !== '') {
                return $configuredTimezone;
            }
        }

        if (! function_exists('geoip')) {
            return null;
        }

        try {
            $location = geoip($ipAddress);
        } catch (Throwable) {
            return null;
        }

        if (is_array($location)) {
            $timezone = $location['timezone'] ?? null;

            return is_string($timezone) && $timezone !== '' ? $timezone : null;
        }

        if (is_object($location) && isset($location->timezone) && is_string($location->timezone) && $location->timezone !== '') {
            return $location->timezone;
        }

        return null;
    }
}
