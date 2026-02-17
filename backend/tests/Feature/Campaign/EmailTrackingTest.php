<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\User;
use App\Services\EmailTrackingTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('open tracking pixel marks recipient as opened', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id, 'type' => 'email']);
    $contact = Contact::factory()->create();

    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'status' => 'sent',
    ]);

    $token = app(EmailTrackingTokenService::class)->encode($recipient->id);

    $response = $this->get('/t/open/'.$token);

    $response->assertStatus(200);

    expect($recipient->refresh()->opened_at)->not->toBeNull();
});

test('click tracking marks recipient and redirects', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id, 'type' => 'email']);

    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => 'sent',
    ]);

    $token = app(EmailTrackingTokenService::class)->encode($recipient->id);
    $destination = 'https://example.com/offer';

    $response = $this->get('/t/click/'.$token.'?url='.urlencode($destination));

    $response->assertRedirect($destination);
    expect($recipient->refresh()->clicked_at)->not->toBeNull();
});

test('duplicate tracking is idempotent', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id, 'type' => 'email']);

    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => 'sent',
    ]);

    $token = app(EmailTrackingTokenService::class)->encode($recipient->id);

    $this->get('/t/open/'.$token)->assertStatus(200);
    $firstOpenedAt = $recipient->refresh()->opened_at;

    $this->get('/t/open/'.$token)->assertStatus(200);

    expect($recipient->refresh()->opened_at?->equalTo($firstOpenedAt))->toBeTrue();
});
