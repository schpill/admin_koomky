<?php

namespace App\Jobs;

use App\Mail\CampaignRecipientMail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowStep;
use App\Services\EmailTrackingTokenService;
use App\Services\MailConfigService;
use App\Services\PersonalizationService;
use App\Services\PreferenceCenterService;
use App\Services\SuppressionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendWorkflowEmailJob implements ShouldQueue
{
    use Queueable;

    public string $enrollmentId;

    public string $stepId;

    public function __construct(public string $workflowEnrollmentId, public string $workflowStepId)
    {
        $this->enrollmentId = $workflowEnrollmentId;
        $this->stepId = $workflowStepId;
        $this->onQueue('campaigns');
    }

    public function handle(
        PersonalizationService $personalizationService,
        EmailTrackingTokenService $tokenService,
        MailConfigService $mailConfigService,
        ?PreferenceCenterService $preferenceCenterService,
        SuppressionService $suppressionService,
    ): void {
        $preferenceCenterService ??= app(PreferenceCenterService::class);
        $enrollment = WorkflowEnrollment::query()
            ->with(['workflow.user', 'contact.client'])
            ->find($this->workflowEnrollmentId);
        $step = WorkflowStep::query()->find($this->workflowStepId);

        if (! $enrollment || ! $step || ! $enrollment->workflow || ! $enrollment->workflow->user || ! $enrollment->contact) {
            return;
        }

        $contact = $enrollment->contact;
        $user = $enrollment->workflow->user;
        /** @var array<string, mixed> $config */
        $config = (array) $step->config;

        if (! is_string($contact->email) || $contact->email === '') {
            $enrollment->update(['status' => 'failed', 'error_message' => 'Contact email is missing.']);

            return;
        }

        if ($suppressionService->isSuppressed($user, $contact->email)) {
            $enrollment->update(['status' => 'cancelled']);

            return;
        }

        $emailCategory = (string) ($config['email_category'] ?? 'promotional');
        if (! $preferenceCenterService->isAllowed($contact, $emailCategory)) {
            $enrollment->update(['status' => 'cancelled']);

            return;
        }

        $campaign = Campaign::query()->create([
            'user_id' => $user->id,
            'segment_id' => null,
            'template_id' => $config['template_id'] ?? null,
            'name' => $enrollment->workflow->name.' - Workflow step',
            'type' => 'email',
            'email_category' => $emailCategory,
            'status' => 'sent',
            'subject' => (string) ($config['subject'] ?? 'Workflow'),
            'content' => (string) ($config['content'] ?? ''),
            'started_at' => now(),
            'completed_at' => now(),
            'settings' => [
                'workflow_id' => $enrollment->workflow_id,
                'workflow_step_id' => $step->id,
            ],
        ]);

        $subject = $personalizationService->render((string) ($config['subject'] ?? 'Workflow'), $contact);
        $body = $personalizationService->render((string) ($config['content'] ?? ''), $contact);

        $recipient = CampaignRecipient::query()->create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'status' => 'pending',
            'metadata' => [
                'workflow_id' => $enrollment->workflow_id,
                'workflow_enrollment_id' => $enrollment->id,
                'workflow_step_id' => $step->id,
            ],
        ]);

        $token = $tokenService->encode($recipient->id);
        $body .= '<img src="'.url('/t/open/'.$token).'" alt="" width="1" height="1" style="display:none;" />';
        $body .= '<p><a href="'.URL::temporarySignedRoute('unsubscribe', now()->addDays(30), ['contact' => $contact->id]).'">Unsubscribe</a></p>';

        $mailer = $mailConfigService->configureForUser($user);

        Mail::mailer($mailer)
            ->to($contact->email)
            ->send(new CampaignRecipientMail($subject !== '' ? $subject : 'Workflow', $body));

        $recipient->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
