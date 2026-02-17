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
            ->with(['campaign.user', 'contact.client'])
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

        $subject = $personalizationService->render((string) ($campaign->subject ?? ''), $contact);
        $body = $personalizationService->render((string) $campaign->content, $contact);

        $token = $tokenService->encode($recipient->id);
        $body = $this->rewriteLinks($body, $token);

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
    }

    private function rewriteLinks(string $html, string $token): string
    {
        return (string) preg_replace_callback('/href=["\']([^"\']+)["\']/i', function (array $matches) use ($token): string {
            $destination = (string) $matches[1];
            $tracking = url('/t/click/'.$token).'?url='.urlencode($destination);

            return 'href="'.$tracking.'"';
        }, $html);
    }
}
