<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('bounce webhook updates recipient status', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);
    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => 'sent',
    ]);

    $response = $this->postJson('/webhooks/email', [
        'event' => 'bounce',
        'recipient_id' => $recipient->id,
    ]);

    $response->assertStatus(200);
    expect($recipient->refresh()->status)->toBe('bounced');
});

test('complaint webhook auto unsubscribes contact', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['email_unsubscribed_at' => null]);

    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'status' => 'sent',
    ]);

    $response = $this->postJson('/webhooks/email', [
        'event' => 'complaint',
        'recipient_id' => $recipient->id,
    ]);

    $response->assertStatus(200);

    expect($recipient->refresh()->status)->toBe('unsubscribed');
    expect($contact->refresh()->email_unsubscribed_at)->not->toBeNull();
});

test('delivery webhook updates status and invalid event returns 400', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);
    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => 'sent',
    ]);

    $delivery = $this->postJson('/webhooks/email', [
        'event' => 'delivery',
        'recipient_id' => $recipient->id,
    ]);

    $delivery->assertStatus(200);
    expect($recipient->refresh()->status)->toBe('delivered');

    $invalid = $this->postJson('/webhooks/email', [
        'event' => 'unexpected',
        'recipient_id' => $recipient->id,
    ]);

    $invalid->assertStatus(400);
});
