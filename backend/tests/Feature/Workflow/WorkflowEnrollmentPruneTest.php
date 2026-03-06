<?php

use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('workflow enrollment prune removes old completed and cancelled enrollments but keeps active ones', function () {
    $user = User::factory()->create();
    $workflow = Workflow::factory()->create(['user_id' => $user->id]);
    $step = WorkflowStep::factory()->create(['workflow_id' => $workflow->id, 'type' => 'end']);
    $workflow->update(['entry_step_id' => $step->id]);

    $oldCompleted = WorkflowEnrollment::factory()->create([
        'workflow_id' => $workflow->id,
        'contact_id' => Contact::factory()->create([
            'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
        ])->id,
        'current_step_id' => $step->id,
        'status' => 'completed',
        'updated_at' => now()->subDays(91),
    ]);

    $oldCancelled = WorkflowEnrollment::factory()->create([
        'workflow_id' => $workflow->id,
        'contact_id' => Contact::factory()->create([
            'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
        ])->id,
        'current_step_id' => $step->id,
        'status' => 'cancelled',
        'updated_at' => now()->subDays(91),
    ]);

    $active = WorkflowEnrollment::factory()->create([
        'workflow_id' => $workflow->id,
        'contact_id' => Contact::factory()->create([
            'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
        ])->id,
        'current_step_id' => $step->id,
        'status' => 'active',
        'updated_at' => now()->subDays(91),
    ]);

    $this->artisan('workflow-enrollments:prune')->assertSuccessful();

    expect(WorkflowEnrollment::query()->find($oldCompleted->id))->toBeNull();
    expect(WorkflowEnrollment::query()->find($oldCancelled->id))->toBeNull();
    expect(WorkflowEnrollment::query()->find($active->id))->not->toBeNull();
});
