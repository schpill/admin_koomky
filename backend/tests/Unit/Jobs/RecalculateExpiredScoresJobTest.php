<?php

use App\Jobs\RecalculateExpiredScoresJob;
use App\Models\Client;
use App\Models\Contact;
use App\Models\ContactScoreEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('job recalculates contacts with expired score events and leaves active scores unchanged', function () {
    $user = User::factory()->create();

    $expiredClient = Client::factory()->create(['user_id' => $user->id]);
    $activeClient = Client::factory()->create(['user_id' => $user->id]);

    $expiredContact = Contact::factory()->create([
        'client_id' => $expiredClient->id,
        'email_score' => 30,
    ]);
    $activeContact = Contact::factory()->create([
        'client_id' => $activeClient->id,
        'email_score' => 20,
    ]);

    ContactScoreEvent::query()->create([
        'user_id' => $user->id,
        'contact_id' => $expiredContact->id,
        'event' => 'email_opened',
        'points' => 10,
        'expires_at' => now()->subHour(),
        'created_at' => now()->subDays(2),
    ]);
    ContactScoreEvent::query()->create([
        'user_id' => $user->id,
        'contact_id' => $expiredContact->id,
        'event' => 'email_clicked',
        'points' => 20,
        'expires_at' => now()->addDays(10),
        'created_at' => now()->subDays(2),
    ]);
    ContactScoreEvent::query()->create([
        'user_id' => $user->id,
        'contact_id' => $activeContact->id,
        'event' => 'email_clicked',
        'points' => 20,
        'expires_at' => now()->addDays(10),
        'created_at' => now()->subDay(),
    ]);

    app(RecalculateExpiredScoresJob::class)->handle(app(\App\Services\ContactScoreService::class));

    expect($expiredContact->fresh()->email_score)->toBe(20)
        ->and($activeContact->fresh()->email_score)->toBe(20);
});
