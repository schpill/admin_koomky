<?php

namespace Tests\Feature\Templates;

use App\Models\ProjectTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTemplateCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_returns_user_templates(): void
    {
        ProjectTemplate::factory()->count(3)->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/project-templates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'tasks_count'],
                ],
            ]);
    }

    public function test_store_creates_template_with_tasks(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/project-templates', [
                'name' => 'Test Template',
                'description' => 'A test template',
                'billing_type' => 'hourly',
                'default_hourly_rate' => 100,
                'tasks' => [
                    ['title' => 'Task 1', 'priority' => 'high'],
                    ['title' => 'Task 2', 'priority' => 'medium'],
                ],
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('project_templates', [
            'user_id' => $this->user->id,
            'name' => 'Test Template',
        ]);

        $template = ProjectTemplate::first();
        $this->assertCount(2, $template->templateTasks);
    }

    public function test_show_returns_template_with_tasks(): void
    {
        $template = ProjectTemplate::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/project-templates/{$template->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $template->id);
    }

    public function test_update_modifies_template(): void
    {
        $template = ProjectTemplate::factory()->for($this->user)->create([
            'name' => 'Original Name',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/v1/project-templates/{$template->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200);

        $template->refresh();
        $this->assertEquals('Updated Name', $template->name);
    }

    public function test_destroy_deletes_template(): void
    {
        $template = ProjectTemplate::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/project-templates/{$template->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('project_templates', ['id' => $template->id]);
    }

    public function test_duplicate_creates_copy(): void
    {
        $template = ProjectTemplate::factory()->for($this->user)->create([
            'name' => 'Original',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/project-templates/{$template->id}/duplicate");

        $response->assertStatus(201);

        $this->assertDatabaseHas('project_templates', [
            'user_id' => $this->user->id,
            'name' => 'Original (Copie)',
        ]);
    }

    public function test_other_user_cannot_access_template(): void
    {
        $otherUser = User::factory()->create();
        $template = ProjectTemplate::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/project-templates/{$template->id}");

        $response->assertStatus(403);
    }
}
