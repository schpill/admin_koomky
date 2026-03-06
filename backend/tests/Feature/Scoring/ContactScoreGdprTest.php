<?php

use App\Models\Client;
use App\Models\Contact;
use App\Models\ContactScoreEvent;
use App\Models\ScoringRule;
use App\Models\User;
use App\Services\DataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('gdpr export includes scoring rules and contact score events for the user only', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $client = Client::factory()->create(['user_id' => $user->id]);
    $contact = Contact::factory()->create(['client_id' => $client->id]);

    $otherClient = Client::factory()->create(['user_id' => $otherUser->id]);
    $otherContact = Contact::factory()->create(['client_id' => $otherClient->id]);

    $rule = ScoringRule::query()->create([
        'user_id' => $user->id,
        'event' => 'email_opened',
        'points' => 10,
        'expiry_days' => 90,
        'is_active' => true,
    ]);

    ScoringRule::query()->create([
        'user_id' => $otherUser->id,
        'event' => 'email_clicked',
        'points' => 20,
        'expiry_days' => 90,
        'is_active' => true,
    ]);

    $event = ContactScoreEvent::query()->create([
        'user_id' => $user->id,
        'contact_id' => $contact->id,
        'event' => 'email_opened',
        'points' => 10,
        'expires_at' => now()->addDays(90),
        'created_at' => now(),
    ]);

    ContactScoreEvent::query()->create([
        'user_id' => $otherUser->id,
        'contact_id' => $otherContact->id,
        'event' => 'email_clicked',
        'points' => 20,
        'expires_at' => now()->addDays(90),
        'created_at' => now(),
    ]);

    $export = app(DataExportService::class)->exportUserData($user);

    expect($export['scoring_rules'])->toHaveCount(1)
        ->and($export['scoring_rules'][0]['id'])->toBe($rule->id)
        ->and($export['contact_score_events'])->toHaveCount(1)
        ->and($export['contact_score_events'][0]['id'])->toBe($event->id);
});
