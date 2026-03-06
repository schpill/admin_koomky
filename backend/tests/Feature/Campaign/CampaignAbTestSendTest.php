<?php

use App\Jobs\SendCampaignEmailJob;
use App\Jobs\SendEmailCampaignJob;
use App\Mail\CampaignRecipientMail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\CampaignVariant;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Services\EmailTrackingTokenService;
use App\Services\MailConfigService;
use App\Services\PersonalizationService;
use App\Services\SegmentFilterEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('ab campaign send assigns variant ids and respects split', function () {
    Queue::fake();

    $user = User::factory()->create();

    foreach (range(1, 10) as $index) {
        $client = Client::factory()->create(['user_id' => $user->id]);
        Contact::factory()->create([
            'client_id' => $client->id,
            'email' => "contact{$index}@example.test",
            'email_unsubscribed_at' => null,
        ]);
    }

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'segment_id' => null,
        'type' => 'email',
        'is_ab_test' => true,
        'ab_winner_criteria' => 'open_rate',
        'ab_auto_select_after_hours' => 6,
    ]);

    $variantA = CampaignVariant::factory()->create([
        'campaign_id' => $campaign->id,
        'label' => 'A',
        'send_percent' => 70,
    ]);

    $variantB = CampaignVariant::factory()->create([
        'campaign_id' => $campaign->id,
        'label' => 'B',
        'send_percent' => 30,
    ]);

    (new SendEmailCampaignJob($campaign->id))->handle(app(SegmentFilterEngine::class));

    $recipients = CampaignRecipient::query()->where('campaign_id', $campaign->id)->get();

    expect($recipients)->toHaveCount(10);
    expect($recipients->where('variant_id', $variantA->id)->count())->toBe(7);
    expect($recipients->where('variant_id', $variantB->id)->count())->toBe(3);
    expect($recipients->whereNull('variant_id')->count())->toBe(0);

    Queue::assertPushed(SendCampaignEmailJob::class, 10);
});

test('send campaign email job uses variant subject and content when present', function () {
    Mail::fake();

    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id, 'name' => 'Acme']);
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'first_name' => 'Lina',
        'email' => 'lina@example.test',
    ]);

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'subject' => 'Default {{first_name}}',
        'content' => 'Default body',
        'is_ab_test' => true,
    ]);

    $variant = CampaignVariant::factory()->create([
        'campaign_id' => $campaign->id,
        'label' => 'A',
        'subject' => 'Variant {{first_name}}',
        'content' => 'Hello {{first_name}} from {{company}}',
    ]);

    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'variant_id' => $variant->id,
        'email' => $contact->email,
        'status' => 'pending',
    ]);

    (new SendCampaignEmailJob($recipient->id))->handle(
        app(PersonalizationService::class),
        app(EmailTrackingTokenService::class),
        app(MailConfigService::class)
    );

    Mail::assertSent(CampaignRecipientMail::class, function (CampaignRecipientMail $mail): bool {
        return $mail->subjectLine === 'Variant Lina'
            && str_contains($mail->htmlBody, 'Hello Lina from Acme');
    });

    expect($variant->fresh()->sent_count)->toBe(1);
});
