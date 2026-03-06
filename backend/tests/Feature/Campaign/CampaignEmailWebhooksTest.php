<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Services\EmailTrackingTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

test('open click bounce and unsubscribe dispatch email webhooks with expected payloads', function () {
    Http::fake(['https://example.com/webhook' => Http::response(['ok' => true], 200)]);

    $user = User::factory()->create();
    $client = Client::query()->create([
        'user_id' => $user->id,
        'reference' => 'CLI-2026-1002',
        'name' => 'Webhook Client',
        'email' => 'client@example.test',
        'phone' => '+33102030405',
        'address' => '1 rue Example',
        'city' => 'Paris',
        'zip_code' => '75001',
        'country' => 'France',
        'industry' => 'Wedding Planner',
        'department' => '75',
        'status' => 'active',
    ]);
    $contact = Contact::factory()->for($client)->create();
    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
    ]);

    WebhookEndpoint::factory()->create([
        'user_id' => $user->id,
        'events' => ['email.opened', 'email.clicked', 'email.bounced', 'email.unsubscribed'],
        'url' => 'https://example.com/webhook',
    ]);

    $recipient = CampaignRecipient::factory()->create([
        'campaign_id' => $campaign->id,
        'contact_id' => $contact->id,
        'status' => 'sent',
        'email' => $contact->email,
    ]);

    $token = app(EmailTrackingTokenService::class)->encode($recipient->id);

    $this->get('/t/open/'.$token)->assertOk();
    $this->get('/t/click/'.$token.'?url='.urlencode('https://example.com/offer'))->assertRedirect('https://example.com/offer');

    $recipient->forceFill([
        'status' => 'bounced',
        'bounce_type' => 'hard',
        'bounced_at' => now(),
    ])->save();

    $unsubscribeUrl = URL::temporarySignedRoute('unsubscribe', now()->addDay(), ['contact' => $contact->id]);
    $this->getJson($unsubscribeUrl)->assertOk();

    Http::assertSentCount(4);

    Http::assertSent(fn ($request) => $request['event'] === 'email.opened' && ($request['data']['contact_id'] ?? null) === $contact->id);
    Http::assertSent(fn ($request) => $request['event'] === 'email.clicked' && ($request['data']['url'] ?? null) === 'https://example.com/offer');
    Http::assertSent(fn ($request) => $request['event'] === 'email.bounced' && ($request['data']['bounce_type'] ?? null) === 'hard');
    Http::assertSent(fn ($request) => $request['event'] === 'email.unsubscribed' && ($request['data']['contact_id'] ?? null) === $contact->id);
});
