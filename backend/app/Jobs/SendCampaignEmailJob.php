<?php

namespace App\Jobs;

use App\Mail\CampaignRecipientMail;
use App\Models\CampaignRecipient;
use App\Services\EmailTrackingTokenService;
use App\Services\MailConfigService;
use App\Services\PersonalizationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendCampaignEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $recipientId) {}

    public function handle(
        PersonalizationService $personalizationService,
        EmailTrackingTokenService $tokenService,
        MailConfigService $mailConfigService
    ): void {
        $recipient = CampaignRecipient::query()
            ->with(['campaign.user', 'contact.client', 'variant'])
            ->find($this->recipientId);

        if (! $recipient || ! $recipient->campaign || ! is_string($recipient->email) || $recipient->email === '') {
            return;
        }

        $campaign = $recipient->campaign;
        $user = $campaign->user;
        if ($user === null) {
            return;
        }
        $contact = $recipient->contact;

        if ($contact === null) {
            return;
        }

        $variant = $recipient->variant;
        $rawSubject = $campaign->subject ?? '';
        $rawBody = $campaign->content;

        if ($variant !== null) {
            $rawSubject = $variant->subject ?? $rawSubject;
            $rawBody = $variant->content ?? $rawBody;
        }

        $token = $tokenService->encode($recipient->id);
        $subject = $personalizationService->render((string) $rawSubject, $contact);
        $body = $personalizationService->render((string) $rawBody, $contact, $token);

        $trackingPixelUrl = url('/t/open/'.$token);
        $unsubscribeUrl = URL::temporarySignedRoute('unsubscribe', now()->addDays(30), ['contact' => $contact->id]);

        $body .= '<hr><p><a href="'.$unsubscribeUrl.'">Unsubscribe</a></p>';
        $body .= '<img src="'.$trackingPixelUrl.'" alt="" width="1" height="1" style="display:none;" />';

        $mailer = $mailConfigService->configureForUser($user);

        Mail::mailer($mailer)
            ->to($recipient->email)
            ->send(new CampaignRecipientMail($subject !== '' ? $subject : 'Campaign', $body));

        $recipient->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        if ($variant !== null) {
            $variant->increment('sent_count');
        }
    }
}
