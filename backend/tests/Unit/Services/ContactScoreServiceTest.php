<?php

use App\Models\Campaign;
use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Services\ContactScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeScoredContact(array $contactOverrides = []): Contact
{
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    return Contact::factory()->create(array_merge([
        'client_id' => $client->id,
        'email' => 'scored@example.test',
    ], $contactOverrides));
}

test('record event inserts default rules when user has none and updates the contact score', function () {
    $contact = makeScoredContact();
    $campaign = Campaign::factory()->create([
        'user_id' => $contact->client->user_id,
        'segment_id' => null,
        'type' => 'email',
    ]);

    $service = app(ContactScoreService::class);

    $service->recordEvent($contact, 'email_opened', $campaign);

    expect(\App\Models\ScoringRule::query()
        ->where('user_id', $contact->client->user_id)
        ->count())->toBe(5);

    $contact->refresh();

    expect($contact->email_score)->toBe(10)
        ->and($contact->email_score_updated_at)->not->toBeNull()
        ->and(\App\Models\ContactScoreEvent::query()->count())->toBe(1)
        ->and(\App\Models\ContactScoreEvent::query()->first()?->source_campaign_id)->toBe($campaign->id);
});

test('recalculate excludes expired score events', function () {
    $contact = makeScoredContact();
    $service = app(ContactScoreService::class);

    $service->recordEvent($contact, 'email_opened');
    $service->recordEvent($contact, 'email_clicked');

    \App\Models\ContactScoreEvent::query()
        ->where('contact_id', $contact->id)
        ->where('event', 'email_opened')
        ->update(['expires_at' => now()->subMinute()]);

    $score = $service->recalculate($contact->fresh());

    expect($score)->toBe(20);
    expect($contact->fresh()->email_score)->toBe(20);
});

test('get history returns newest score events first', function () {
    $contact = makeScoredContact();
    $service = app(ContactScoreService::class);

    $service->recordEvent($contact, 'campaign_sent');
    $service->recordEvent($contact, 'email_clicked');

    $history = $service->getHistory($contact);

    expect($history)->toHaveCount(2)
        ->and($history->pluck('event')->all())->toBe(['email_clicked', 'campaign_sent']);
});
