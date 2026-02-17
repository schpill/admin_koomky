<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('calendar settings endpoint returns default auto event rules', function () {
    $user = User::factory()->create([
        'notification_preferences' => null,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/settings/calendar');

    $response->assertStatus(200)
        ->assertJsonPath('data.auto_events.project_deadlines', true)
        ->assertJsonPath('data.auto_events.task_due_dates', true)
        ->assertJsonPath('data.auto_events.invoice_reminders', true);
});

test('calendar settings can be updated without overriding other notification preferences', function () {
    $user = User::factory()->create([
        'notification_preferences' => [
            'invoice_paid' => ['email' => true, 'in_app' => true],
            'campaign_completed' => ['email' => true, 'in_app' => false],
            'task_overdue' => ['email' => false, 'in_app' => true],
        ],
    ]);

    $payload = [
        'auto_events' => [
            'project_deadlines' => false,
            'task_due_dates' => true,
            'invoice_reminders' => false,
        ],
    ];

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/settings/calendar', $payload)
        ->assertStatus(200)
        ->assertJsonPath('data.auto_events.project_deadlines', false)
        ->assertJsonPath('data.auto_events.task_due_dates', true)
        ->assertJsonPath('data.auto_events.invoice_reminders', false);

    $fresh = $user->fresh();

    expect($fresh)->not->toBeNull();
    expect($fresh->notification_preferences['invoice_paid']['email'])->toBeTrue();
    expect($fresh->notification_preferences['calendar_auto_events'])->toBe($payload['auto_events']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/settings/calendar')
        ->assertStatus(200)
        ->assertJsonPath('data.auto_events.project_deadlines', false)
        ->assertJsonPath('data.auto_events.task_due_dates', true)
        ->assertJsonPath('data.auto_events.invoice_reminders', false);
});

test('calendar settings validation requires boolean auto event flags', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/settings/calendar', [
            'auto_events' => [
                'project_deadlines' => 'yes',
                'task_due_dates' => true,
                'invoice_reminders' => false,
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('auto_events.project_deadlines');
});
