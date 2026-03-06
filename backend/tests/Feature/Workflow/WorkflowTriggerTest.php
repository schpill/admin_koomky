<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Services\EmailTrackingTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createTriggeredWorkflow(User $user, string $triggerType, array $triggerConfig = []): Workflow
{
    $workflow = Workflow::factory()->create([
        'user_id' => $user->id,
        'trigger_type' => $triggerType,
        'trigger_config' => $triggerConfig,
        'status' => 'active',
    ]);
    $step = WorkflowStep::factory()->create([
        'workflow_id' => $workflow->id,
        'type' => 'end',
    ]);
    $workflow->update(['entry_step_id' => $step->id]);

    return $workflow->fresh();
}

test('email opened enrolls contact in matching active workflow', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['client_id' => $client->id]);
    $campaign = Campaign::factory()->create(['user_id' => $user->id, 'type' => 'email']);
    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'email' => $contact->email,
        'status' => 'sent',
    ]);
    createTriggeredWorkflow($user, 'email_opened', ['campaign_id' => $campaign->id]);

    $token = app(EmailTrackingTokenService::class)->encode($recipient->id);

    $this->get('/t/open/'.$token)->assertOk();

    expect(Workflow::query()->firstOrFail()->enrollments()->where('contact_id', $contact->id)->count())->toBe(1);
});

test('score threshold enrolls only when threshold reached', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'email_score' => 30,
    ]);
    $workflow = createTriggeredWorkflow($user, 'score_threshold', ['threshold' => 50]);

    app(\App\Services\ContactScoreService::class)->recalculate($contact->fresh());
    expect($workflow->fresh()->enrollments()->count())->toBe(0);

    $contact->update(['email_score' => 60]);
    app(\App\Services\ContactScoreService::class)->recalculate($contact->fresh());

    expect($workflow->fresh()->enrollments()->count())->toBe(1);
});

test('contact created enrolls contact in active workflow', function () {
    $user = User::factory()->create();
    createTriggeredWorkflow($user, 'contact_created');
    $client = Client::factory()->create(['user_id' => $user->id]);

    $contact = Contact::factory()->create(['client_id' => $client->id]);

    expect(Workflow::query()->firstOrFail()->enrollments()->where('contact_id', $contact->id)->count())->toBe(1);
});
