<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('user can upload and download task attachment', function () {
    $diskRoot = '/tmp/koomky-test-attachments-'.uniqid();
    config(['filesystems.disks.attachments.root' => $diskRoot]);

    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $task = Task::factory()->create(['project_id' => $project->id]);

    $uploadResponse = $this->actingAs($user, 'sanctum')
        ->post('/api/v1/projects/'.$project->id.'/tasks/'.$task->id.'/attachments', [
            'file' => UploadedFile::fake()->create('specification.pdf', 200, 'application/pdf'),
        ], ['Accept' => 'application/json']);

    $uploadResponse->assertStatus(201)
        ->assertJsonPath('data.filename', 'specification.pdf');

    $attachmentId = (string) $uploadResponse->json('data.id');
    $attachment = TaskAttachment::query()->findOrFail($attachmentId);

    Storage::disk('attachments')->assertExists($attachment->path);

    $downloadResponse = $this->actingAs($user, 'sanctum')
        ->get('/api/v1/projects/'.$project->id.'/tasks/'.$task->id.'/attachments/'.$attachmentId);

    $downloadResponse->assertStatus(200);
});

test('task attachment upload rejects files larger than 10mb', function () {
    $diskRoot = '/tmp/koomky-test-attachments-'.uniqid();
    config(['filesystems.disks.attachments.root' => $diskRoot]);

    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $task = Task::factory()->create(['project_id' => $project->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->post('/api/v1/projects/'.$project->id.'/tasks/'.$task->id.'/attachments', [
            'file' => UploadedFile::fake()->create('too-large.zip', 10241, 'application/zip'),
        ], ['Accept' => 'application/json']);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

test('task attachment upload rejects when cumulative size exceeds 50mb', function () {
    $diskRoot = '/tmp/koomky-test-attachments-'.uniqid();
    config(['filesystems.disks.attachments.root' => $diskRoot]);

    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $task = Task::factory()->create(['project_id' => $project->id]);

    // 5x 10MB accepted
    foreach (range(1, 5) as $index) {
        $this->actingAs($user, 'sanctum')
            ->post('/api/v1/projects/'.$project->id.'/tasks/'.$task->id.'/attachments', [
                'file' => UploadedFile::fake()->create("f{$index}.bin", 10240, 'application/octet-stream'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(201);
    }

    // 6th file should exceed 50MB cumulative cap
    $response = $this->actingAs($user, 'sanctum')
        ->post('/api/v1/projects/'.$project->id.'/tasks/'.$task->id.'/attachments', [
            'file' => UploadedFile::fake()->create('overflow.bin', 1024, 'application/octet-stream'),
        ], ['Accept' => 'application/json']);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Total attachment size limit exceeded for this task');
});
