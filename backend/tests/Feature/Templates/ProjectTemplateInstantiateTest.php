<?php

namespace Tests\Feature\Templates;

use App\Models\Client;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTemplateInstantiateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Client $client;
    protected ProjectTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->client = Client::factory()->for($this->user)->create();
        $this->template = ProjectTemplate::factory()->for($this->user)->create();

        ProjectTemplateTask::factory()->for($this->template)->create(['title' => 'Task 1', 'sort_order' => 0]);
        ProjectTemplateTask::factory()->for($this->template)->create(['title' => 'Task 2', 'sort_order' => 1]);
    }

    public function test_instantiate_creates_project_with_tasks(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/project-templates/{$this->template->id}/instantiate", [
                'name' => 'New Project',
                'client_id' => $this->client->id,
                'start_date' => '2026-03-01',
                'deadline' => '2026-04-01',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'client_id', 'tasks_count'],
            ]);

        $this->assertDatabaseHas('projects', [
            'user_id' => $this->user->id,
            'client_id' => $this->client->id,
            'name' => 'New Project',
        ]);

        $project = \App\Models\Project::first();
        $this->assertCount(2, $project->tasks);
    }

    public function test_instantiate_validates_client_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/project-templates/{$this->template->id}/instantiate", [
                'name' => 'Project',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['client_id']);
    }

    public function test_instantiate_validates_deadline_after_start_date(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/project-templates/{$this->template->id}/instantiate", [
                'name' => 'Project',
                'client_id' => $this->client->id,
                'start_date' => '2026-04-01',
                'deadline' => '2026-03-01',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['deadline']);
    }

    public function test_other_user_cannot_instantiate_template(): void
    {
        $otherUser = User::factory()->create();
        $otherClient = Client::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/project-templates/{$this->template->id}/instantiate", [
                'name' => 'Project',
                'client_id' => $otherClient->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_other_user_template_returns_403(): void
    {
        $otherUser = User::factory()->create();
        $otherTemplate = ProjectTemplate::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/project-templates/{$otherTemplate->id}/instantiate", [
                'name' => 'Project',
                'client_id' => $this->client->id,
            ]);

        $response->assertStatus(403);
    }
}