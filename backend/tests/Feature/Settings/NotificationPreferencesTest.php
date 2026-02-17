<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can update notification preferences with validation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/settings/notifications', [
            'invoice_paid' => ['email' => true, 'in_app' => true],
            'campaign_completed' => ['email' => true, 'in_app' => false],
            'task_overdue' => ['email' => false, 'in_app' => true],
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.notification_preferences.invoice_paid.email', true);

    $invalid = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/settings/notifications', [
            'invoice_paid' => ['email' => 'yes'],
        ]);

    $invalid->assertStatus(422);
});
