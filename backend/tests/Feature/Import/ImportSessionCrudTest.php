<?php

use App\Jobs\ProcessProspectImportJob;
use App\Models\ImportSession;
use App\Models\ImportSessionError;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Queue::fake();
});

test('import session crud endpoints handle csv and validations', function () {
    $user = User::factory()->create();

    $csv = UploadedFile::fake()->createWithContent('prospects.csv', "Nom,Email\nAcme,acme@example.com\n");

    $created = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/import-sessions', ['file' => $csv]);

    $created->assertCreated()->assertJsonStructure([
        'data' => ['session' => ['id'], 'column_list', 'preview_rows', 'detected_mapping'],
    ]);

    $sessionId = $created->json('data.session.id');

    $show = $this->actingAs($user, 'sanctum')->getJson("/api/v1/import-sessions/{$sessionId}");
    $show->assertOk()->assertJsonPath('data.total_rows', 1);

    $patched = $this->actingAs($user, 'sanctum')->patchJson("/api/v1/import-sessions/{$sessionId}", [
        'column_mapping' => ['Nom' => 'name', 'Email' => 'email'],
        'default_tags' => ['wedding'],
        'options' => ['duplicate_strategy' => 'skip', 'default_status' => 'prospect'],
    ]);
    $patched->assertOk()->assertJsonPath('data.status', 'mapping');

    $process = $this->actingAs($user, 'sanctum')->postJson("/api/v1/import-sessions/{$sessionId}/process");
    $process->assertStatus(202);
    Queue::assertPushed(ProcessProspectImportJob::class);

    ImportSessionError::factory()->create(['session_id' => $sessionId, 'row_number' => 3]);

    $errors = $this->actingAs($user, 'sanctum')->getJson("/api/v1/import-sessions/{$sessionId}/errors");
    $errors->assertOk()->assertJsonPath('data.total', 1);

    $csvErrors = $this->actingAs($user, 'sanctum')->get("/api/v1/import-sessions/{$sessionId}/errors/export");
    $csvErrors->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    ImportSession::query()->where('id', $sessionId)->update(['status' => 'failed']);
    $deleted = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/import-sessions/{$sessionId}");
    $deleted->assertStatus(204);
});

test('ownership is enforced and invalid uploads are rejected', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $session = ImportSession::factory()->create(['user_id' => $other->id]);

    $forbidden = $this->actingAs($user, 'sanctum')->getJson("/api/v1/import-sessions/{$session->id}");
    $forbidden->assertForbidden();

    $invalid = UploadedFile::fake()->create('invalid.pdf', 100, 'application/pdf');
    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/import-sessions', ['file' => $invalid]);
    $response->assertStatus(422);
});
