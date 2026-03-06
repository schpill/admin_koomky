<?php

namespace App\Jobs;

use App\Mail\CampaignRecipientMail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\DripEnrollment;
use App\Models\DripStep;
use App\Services\EmailTrackingTokenService;
use App\Services\MailConfigService;
use App\Services\PersonalizationService;
use App\Services\PreferenceCenterService;
use App\Services\SuppressionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendDripStepEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $enrollmentId, public string $stepId)
    {
        $this->onQueue('campaigns');
    }

    public function handle(
        PersonalizationService $personalizationService,
        EmailTrackingTokenService $tokenService,
        MailConfigService $mailConfigService,
        ?PreferenceCenterService $preferenceCenterService = null,
        ?SuppressionService $suppressionService = null
    ): void {
        $preferenceCenterService ??= app(PreferenceCenterService::class);
        $suppressionService ??= app(SuppressionService::class);

        $enrollment = DripEnrollment::query()
            ->with(['sequence.user', 'contact.client'])
            ->find($this->enrollmentId);
        $step = DripStep::query()->find($this->stepId);

        if (! $enrollment || ! $step || ! $enrollment->sequence || ! $enrollment->sequence->user || ! $enrollment->contact) {
            return;
        }

        $contact = $enrollment->contact;
        $user = $enrollment->sequence->user;

        if (! is_string($contact->email) || $contact->email === '') {
            $enrollment->update(['status' => 'failed']);

            return;
        }

        if ($suppressionService->isSuppressed($user, $contact->email)) {
            $enrollment->update(['status' => 'cancelled']);

            return;
        }

        $emailCategory = (string) data_get($step->template?->settings, 'email_category', 'promotional');
        if (! $preferenceCenterService->isAllowed($contact, $emailCategory)) {
            $enrollment->update(['status' => 'cancelled']);

            return;
        }

        $campaign = Campaign::query()->create([
            'user_id' => $user->id,
            'segment_id' => null,
            'template_id' => $step->template_id,
            'name' => $enrollment->sequence->name.' - Step '.$step->position,
            'type' => 'email',
            'email_category' => $emailCategory,
            'status' => 'sent',
            'subject' => $step->subject,
            'content' => $step->content,
            'started_at' => now(),
            'completed_at' => now(),
            'settings' => [
                'drip_sequence_id' => $enrollment->sequence_id,
                'drip_step_id' => $step->id,
            ],
        ]);

        $subject = $personalizationService->render($step->subject, $contact);
        $body = $personalizationService->render($step->content, $contact);

        $recipient = CampaignRecipient::query()->create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'status' => 'pending',
            'metadata' => [
                'drip_sequence_id' => $enrollment->sequence_id,
                'drip_enrollment_id' => $enrollment->id,
                'drip_step_id' => $step->id,
                'drip_step_position' => $step->position,
            ],
        ]);

        $token = $tokenService->encode($recipient->id);
        $trackingPixelUrl = url('/t/open/'.$token);
        $unsubscribeUrl = URL::temporarySignedRoute('unsubscribe', now()->addDays(30), ['contact' => $contact->id]);
        $body .= '<hr><p><a href="'.$unsubscribeUrl.'">Unsubscribe</a></p>';
        $body .= '<img src="'.$trackingPixelUrl.'" alt="" width="1" height="1" style="display:none;" />';

        $mailer = $mailConfigService->configureForUser($user);

        Mail::mailer($mailer)
            ->to($contact->email)
            ->send(new CampaignRecipientMail($subject !== '' ? $subject : 'Drip', $body));

        $recipient->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $nextPosition = $step->position;
        $finalPosition = (int) ($enrollment->sequence->steps()->max('position') ?? $nextPosition);

        $enrollment->update([
            'current_step_position' => $nextPosition,
            'last_processed_at' => now(),
            'status' => $nextPosition >= $finalPosition ? 'completed' : 'active',
            'completed_at' => $nextPosition >= $finalPosition ? now() : null,
        ]);
    }
}
