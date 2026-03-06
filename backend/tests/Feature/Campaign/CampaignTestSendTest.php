<?php

use App\Mail\CampaignTestMail;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('test send endpoint sends to multiple email recipients with preview personalization', function () {
    Mail::fake();

    $user = User::factory()->create();
    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'status' => 'draft',
        'subject' => 'Bonjour {{first_name}}',
        'content' => '<p>{{first_name}} - {{client.industry}}</p>',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/campaigns/'.$campaign->id.'/test', [
            'emails' => ['a@example.test', 'b@example.test', 'c@example.test'],
        ]);

    $response->assertStatus(200);

    Mail::assertSent(CampaignTestMail::class, 3);
    Mail::assertSent(CampaignTestMail::class, function (CampaignTestMail $mail): bool {
        return str_contains($mail->renderedSubject, 'Marie')
            && str_contains($mail->renderedBody, 'Wedding Planner')
            && ! str_contains($mail->renderedBody, '{{first_name}}');
    });
});

test('test send endpoint validates email list boundaries and format', function () {
    Mail::fake();

    $user = User::factory()->create();
    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'type' => 'email',
        'status' => 'draft',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/campaigns/'.$campaign->id.'/test', ['emails' => []])
        ->assertStatus(422);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/campaigns/'.$campaign->id.'/test', [
            'emails' => [
                'a@example.test',
                'b@example.test',
                'c@example.test',
                'd@example.test',
                'e@example.test',
                'f@example.test',
            ],
        ])
        ->assertStatus(422);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/campaigns/'.$campaign->id.'/test', [
            'emails' => ['not-an-email'],
        ])
        ->assertStatus(422);
});

test('campaign test send enforces ownership', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $campaign = Campaign::factory()->create([
        'user_id' => $owner->id,
        'type' => 'email',
        'status' => 'draft',
    ]);

    $this->actingAs($other, 'sanctum')
        ->postJson('/api/v1/campaigns/'.$campaign->id.'/test', [
            'emails' => ['qa@example.test'],
        ])
        ->assertStatus(403);
});
