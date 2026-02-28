<?php

namespace Tests\Feature\Templates;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTemplateSaveTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->project = Project::factory()->for($this->user)->create();
    }

    public function test_save_as_template_creates_template_from_project(): void
    {
        Task::factory()->count(3)->for($this->project)->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/save-as-template", [
                'name' => 'Template from Project',
                'description' => 'Created from project',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('project_templates', [
            'user_id' => $this->user->id,
            'name' => 'Template from Project',
            'description' => 'Created from project',
        ]);
    }

    public function test_save_as_template_includes_project_tasks(): void
    {
        Task::factory()->for($this->project)->create(['title' => 'Project Task 1']);
        Task::factory()->for($this->project)->create(['title' => 'Project Task 2']);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/save-as-template", [
                'name' => 'Template with tasks',
            ]);

        $response->assertStatus(201);

        $template = \App\Models\ProjectTemplate::first();
        $this->assertCount(2, $template->templateTasks);
    }

    public function test_save_as_template_without_tasks(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/save-as-template", [
                'name' => 'Empty Template',
            ]);

        $response->assertStatus(201);

        $template = \App\Models\ProjectTemplate::first();
        $this->assertCount(0, $template->templateTasks);
    }

    public function test_save_as_template_validates_name_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$this->project->id}/save-as-template", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_other_user_cannot_save_project_as_template(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/projects/{$otherProject->id}/save-as-template", [
                'name' => 'Unauthorized',
            ]);

        $response->assertStatus(403);
    }
}
