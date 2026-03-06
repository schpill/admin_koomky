<?php

use App\Jobs\SendEmailCampaignJob;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Segment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('promotional campaign skips contacts opted out from that category', function () {
    Queue::fake();

    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id, 'city' => 'Paris']);
    $allowedContact = Contact::factory()->create([
        'client_id' => $client->id,
        'email' => 'allowed@campaign.test',
        'email_unsubscribed_at' => null,
    ]);
    $blockedContact = Contact::factory()->create([
        'client_id' => $client->id,
        'email' => 'blocked@campaign.test',
        'email_unsubscribed_at' => null,
    ]);

    app(\App\Services\PreferenceCenterService::class)->updatePreference($blockedContact, 'promotional', false);

    $segment = Segment::factory()->create([
        'user_id' => $user->id,
        'filters' => [
            'groups' => [[
                'criteria' => [[
                    'type' => 'location',
                    'field' => 'city',
                    'operator' => 'equals',
                    'value' => 'Paris',
                ]],
            ]],
        ],
    ]);

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'segment_id' => $segment->id,
        'type' => 'email',
        'status' => 'draft',
        'subject' => 'Promo',
        'content' => '<p>Promo</p>',
        'email_category' => 'promotional',
    ]);

    Queue::fake();
    app(SendEmailCampaignJob::class, ['campaignId' => $campaign->id])->handle(app(\App\Services\SegmentFilterEngine::class));

    $this->assertDatabaseHas('campaign_recipients', [
        'campaign_id' => $campaign->id,
        'contact_id' => $allowedContact->id,
        'email' => 'allowed@campaign.test',
    ]);
    $this->assertDatabaseMissing('campaign_recipients', [
        'campaign_id' => $campaign->id,
        'contact_id' => $blockedContact->id,
        'email' => 'blocked@campaign.test',
    ]);
});

test('transactional campaign still sends to contacts with opted out promotional preference', function () {
    Queue::fake();

    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id, 'city' => 'Paris']);
    $contact = Contact::factory()->create([
        'client_id' => $client->id,
        'email' => 'transactional@campaign.test',
        'email_unsubscribed_at' => null,
    ]);

    app(\App\Services\PreferenceCenterService::class)->updatePreference($contact, 'promotional', false);

    $segment = Segment::factory()->create([
        'user_id' => $user->id,
        'filters' => [
            'groups' => [[
                'criteria' => [[
                    'type' => 'location',
                    'field' => 'city',
                    'operator' => 'equals',
                    'value' => 'Paris',
                ]],
            ]],
        ],
    ]);

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'segment_id' => $segment->id,
        'type' => 'email',
        'status' => 'draft',
        'subject' => 'Receipt',
        'content' => '<p>Receipt</p>',
        'email_category' => 'transactional',
    ]);

    app(SendEmailCampaignJob::class, ['campaignId' => $campaign->id])->handle(app(\App\Services\SegmentFilterEngine::class));

    $this->assertDatabaseHas('campaign_recipients', [
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'email' => 'transactional@campaign.test',
    ]);
});
