<?php

use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('gdpr export includes workflow enrollments for the authenticated user only', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $ownerWorkflow = Workflow::factory()->create(['user_id' => $owner->id]);
    $ownerStep = WorkflowStep::factory()->create(['workflow_id' => $ownerWorkflow->id, 'type' => 'end']);
    $ownerWorkflow->update(['entry_step_id' => $ownerStep->id]);
    $ownerEnrollment = WorkflowEnrollment::factory()->create([
        'workflow_id' => $ownerWorkflow->id,
        'contact_id' => Contact::factory()->create([
            'client_id' => Client::factory()->create(['user_id' => $owner->id])->id,
        ])->id,
        'current_step_id' => $ownerStep->id,
    ]);

    $otherWorkflow = Workflow::factory()->create(['user_id' => $other->id]);
    $otherStep = WorkflowStep::factory()->create(['workflow_id' => $otherWorkflow->id, 'type' => 'end']);
    $otherWorkflow->update(['entry_step_id' => $otherStep->id]);
    WorkflowEnrollment::factory()->create([
        'workflow_id' => $otherWorkflow->id,
        'contact_id' => Contact::factory()->create([
            'client_id' => Client::factory()->create(['user_id' => $other->id])->id,
        ])->id,
        'current_step_id' => $otherStep->id,
    ]);

    $response = $this->actingAs($owner, 'sanctum')->get('/api/v1/export/full');

    $response->assertOk();
    $archivePath = tempnam(sys_get_temp_dir(), 'workflow-gdpr-');
    file_put_contents($archivePath, $response->streamedContent());

    $zip = new ZipArchive;
    expect($zip->open($archivePath))->toBeTrue();

    $payload = json_decode((string) $zip->getFromName('export.json'), true, 512, JSON_THROW_ON_ERROR);
    $zip->close();

    $enrollmentIds = collect($payload['workflow_enrollments'] ?? [])->pluck('id')->all();

    expect($enrollmentIds)->toContain($ownerEnrollment->id);
    expect($enrollmentIds)->toHaveCount(1);
});
