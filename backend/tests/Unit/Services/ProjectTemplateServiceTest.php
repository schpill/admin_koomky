<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateTask;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProjectTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectTemplateService();
    }

    public function test_create_from_project_copies_fields(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create([
            'billing_type' => 'hourly',
            'hourly_rate' => 150.00,
            'currency' => 'EUR',
            'estimated_hours' => 40.00,
        ]);

        $template = $this->service->createFromProject($project, 'Test Template', 'Test description');

        $this->assertEquals('Test Template', $template->name);
        $this->assertEquals('Test description', $template->description);
        $this->assertEquals('hourly', $template->billing_type);
        $this->assertEquals(150.00, $template->default_hourly_rate);
        $this->assertEquals('EUR', $template->default_currency);
        $this->assertEquals(40.00, $template->estimated_hours);
    }

    public function test_create_from_project_copies_tasks_with_sort_order(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user)->create();

        $task1 = Task::factory()->for($project)->create(['title' => 'Task 1', 'sort_order' => 0]);
        $task2 = Task::factory()->for($project)->create(['title' => 'Task 2', 'sort_order' => 1]);
        $task3 = Task::factory()->for($project)->create(['title' => 'Task 3', 'sort_order' => 2]);

        $template = $this->service->createFromProject($project, 'Template with tasks');

        $this->assertCount(3, $template->templateTasks);

        $tasks = $template->templateTasks()->ordered()->get();
        $this->assertEquals('Task 1', $tasks[0]->title);
        $this->assertEquals(0, $tasks[0]->sort_order);
        $this->assertEquals('Task 2', $tasks[1]->title);
        $this->assertEquals('Task 3', $tasks[2]->title);
    }

    public function test_instantiate_creates_project_with_template_data(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();

        $template = ProjectTemplate::factory()->for($user)->create([
            'name' => 'Template',
            'billing_type' => 'hourly',
            'default_hourly_rate' => 100.00,
            'default_currency' => 'EUR',
            'estimated_hours' => 20.00,
        ]);

        ProjectTemplateTask::factory()->for($template, 'template')->create([
            'title' => 'Task A',
            'sort_order' => 0,
        ]);
        ProjectTemplateTask::factory()->for($template, 'template')->create([
            'title' => 'Task B',
            'sort_order' => 1,
        ]);

        $project = $this->service->instantiate($template, [
            'name' => 'New Project',
            'client_id' => $client->id,
            'start_date' => '2026-03-01',
            'deadline' => '2026-04-01',
        ], $user);

        $this->assertEquals('New Project', $project->name);
        $this->assertEquals($client->id, $project->client_id);
        $this->assertEquals('hourly', $project->billing_type);
        $this->assertEquals(100.00, $project->hourly_rate);
        $this->assertEquals('EUR', $project->currency);
        $this->assertEquals('2026-03-01', $project->start_date?->toDateString());
        $this->assertEquals('2026-04-01', $project->deadline?->toDateString());
        $this->assertEquals('draft', $project->status);

        $this->assertCount(2, $project->tasks);
        $this->assertEquals('Task A', $project->tasks[0]->title);
        $this->assertEquals('Task B', $project->tasks[1]->title);
    }

    public function test_instantiate_uses_template_name_when_not_provided(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->for($user)->create();

        $template = ProjectTemplate::factory()->for($user)->create(['name' => 'Original Template']);

        $project = $this->service->instantiate($template, [
            'client_id' => $client->id,
        ], $user);

        $this->assertEquals('Original Template', $project->name);
    }
}
