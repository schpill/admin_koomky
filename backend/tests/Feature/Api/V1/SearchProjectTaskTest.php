<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('search response includes projects and tasks keys', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'name' => 'Landing page refresh',
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'title' => 'Design wireframes',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/search?q=Design');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => ['clients', 'projects', 'tasks', 'invoices'],
        ]);
});
