<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Services\ContactSendTimeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('get optimal hour returns the most frequent opened hour when there is enough history', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['client_id' => $client->id]);
    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'segment_id' => null,
        'type' => 'email',
    ]);

    foreach ([9, 9, 14, 9] as $hour) {
        CampaignRecipient::factory()->create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'opened_at' => now()->startOfDay()->addHours($hour),
        ]);
    }

    $hour = app(ContactSendTimeService::class)->getOptimalHour($contact, $user);

    expect($hour)->toBe(9);
});

test('get optimal hour returns null when there are fewer than three opens', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['client_id' => $client->id]);
    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'segment_id' => null,
        'type' => 'email',
    ]);

    foreach ([9, 9] as $hour) {
        CampaignRecipient::factory()->create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'opened_at' => now()->startOfDay()->addHours($hour),
        ]);
    }

    $hour = app(ContactSendTimeService::class)->getOptimalHour($contact, $user);

    expect($hour)->toBeNull();
});

test('get next send delay returns a delay within the configured window', function () {
    $delay = app(ContactSendTimeService::class)->getNextSendDelay(10, 24);

    expect($delay)->toBeInt()
        ->toBeGreaterThanOrEqual(0)
        ->toBeLessThanOrEqual(24 * 3600);
});
