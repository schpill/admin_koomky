<?php

use App\Models\ReminderSequence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function reminderPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Relance standard',
        'description' => 'Relance en 3 étapes',
        'is_active' => true,
        'is_default' => false,
        'steps' => [
            ['step_number' => 1, 'delay_days' => 3, 'subject' => 'Rappel 1', 'body' => 'Bonjour {{client_name}}'],
            ['step_number' => 2, 'delay_days' => 7, 'subject' => 'Rappel 2', 'body' => 'Relance {{invoice_number}}'],
        ],
    ], $overrides);
}

test('reminder sequence crud and set default works', function () {
    $user = User::factory()->create();

    $create = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/reminder-sequences', reminderPayload());

    $create->assertStatus(201)
        ->assertJsonPath('data.name', 'Relance standard');

    $id = $create->json('data.id');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/reminder-sequences')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/reminder-sequences/'.$id)
        ->assertStatus(200)
        ->assertJsonPath('data.id', $id);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/reminder-sequences/'.$id, reminderPayload([
            'name' => 'Relance maj',
            'steps' => [
                ['step_number' => 1, 'delay_days' => 5, 'subject' => 'Sujet', 'body' => 'Body'],
            ],
        ]))
        ->assertStatus(200)
        ->assertJsonPath('data.name', 'Relance maj')
        ->assertJsonCount(1, 'data.steps');

    $other = ReminderSequence::factory()->create([
        'user_id' => $user->id,
        'is_default' => true,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/reminder-sequences/'.$id.'/default')
        ->assertStatus(200)
        ->assertJsonPath('data.is_default', true);

    expect($other->fresh()?->is_default)->toBeFalse();

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/reminder-sequences/'.$id)
        ->assertStatus(204);
});

test('reminder sequence ownership is enforced', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $sequence = ReminderSequence::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($other, 'sanctum')
        ->getJson('/api/v1/reminder-sequences/'.$sequence->id)
        ->assertStatus(403);
});
