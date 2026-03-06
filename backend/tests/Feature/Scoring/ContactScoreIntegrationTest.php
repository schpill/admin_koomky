<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Services\EmailTrackingTokenService;
use App\Services\SegmentFilterEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

test('open click unsubscribe and hard bounce update the contact email score', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'email' => 'contact@example.test',
    ]);
    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'segment_id' => null,
        'type' => 'email',
    ]);

    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'email' => $contact->email,
        'status' => 'sent',
    ]);

    $token = app(EmailTrackingTokenService::class)->encode($recipient->id);

    $this->get('/t/open/'.$token)->assertStatus(200);
    expect($contact->fresh()->email_score)->toBe(10);

    $this->get('/t/click/'.$token.'?url='.urlencode('https://example.test'))->assertRedirect('https://example.test');
    expect($contact->fresh()->email_score)->toBe(30);

    $unsubscribeUrl = URL::temporarySignedRoute('unsubscribe', now()->addMinutes(30), [
        'contact' => $contact->id,
    ]);

    $this->getJson($unsubscribeUrl)->assertOk();
    expect($contact->fresh()->email_score)->toBe(-20);

    $recipient->update([
        'status' => 'bounced',
        'bounce_type' => 'hard',
        'bounced_at' => now(),
    ]);

    expect($contact->fresh()->email_score)->toBe(-25);
});

test('segment filter engine can filter contacts by email score', function () {
    $user = User::factory()->create();
    $highClient = Client::factory()->create(['user_id' => $user->id]);
    $lowClient = Client::factory()->create(['user_id' => $user->id]);

    $high = Contact::factory()->create([
        'client_id' => $highClient->id,
        'email_score' => 60,
    ]);
    $low = Contact::factory()->create([
        'client_id' => $lowClient->id,
        'email_score' => 10,
    ]);

    $results = app(SegmentFilterEngine::class)->apply($user, [
        'groups' => [
            [
                'criteria' => [
                    ['type' => 'email_score', 'operator' => 'gte', 'value' => 50],
                ],
            ],
        ],
    ])->pluck('id')->all();

    expect($results)->toContain($high->id);
    expect($results)->not->toContain($low->id);
});
