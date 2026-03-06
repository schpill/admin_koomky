<?php

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('workflow crud is scoped to owner', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $workflow = Workflow::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($intruder, 'sanctum')
        ->getJson('/api/v1/workflows/'.$workflow->id)
        ->assertForbidden();
});

test('workflow activation refuses missing entry step', function () {
    $user = User::factory()->create();
    $workflow = Workflow::factory()->create([
        'user_id' => $user->id,
        'status' => 'draft',
        'entry_step_id' => null,
    ]);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/workflows/'.$workflow->id.'/activate')
        ->assertStatus(422)
        ->assertJsonPath('message', 'Workflow entry step is required');
});

test('workflow owner can create activate update and delete a workflow', function () {
    $user = User::factory()->create();

    $createResponse = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/workflows', [
            'name' => 'Lifecycle',
            'description' => 'Test workflow',
            'trigger_type' => 'manual',
            'trigger_config' => [],
            'status' => 'draft',
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Lifecycle');

    $workflowId = (string) $createResponse->json('data.id');

    $stepResponse = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/workflows/'.$workflowId.'/steps', [
            'type' => 'end',
            'config' => [],
            'position_x' => 120,
            'position_y' => 60,
        ]);

    $stepResponse->assertCreated();
    $stepId = (string) $stepResponse->json('data.id');

    Workflow::query()->findOrFail($workflowId)->update(['entry_step_id' => $stepId]);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/workflows/'.$workflowId.'/activate')
        ->assertOk()
        ->assertJsonPath('data.status', 'active');

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/workflows/'.$workflowId, [
            'name' => 'Lifecycle Updated',
            'description' => 'Updated',
            'trigger_type' => 'manual',
            'trigger_config' => [],
            'status' => 'paused',
            'entry_step_id' => $stepId,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Lifecycle Updated');

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/workflows/'.$workflowId)
        ->assertNoContent();
});
