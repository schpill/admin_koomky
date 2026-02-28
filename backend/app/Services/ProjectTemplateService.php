<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateTask;
use App\Models\Task;
use App\Models\User;
use App\Services\ReferenceGenerator;

class ProjectTemplateService
{
    /**
     * Create a template from an existing project.
     */
    public function createFromProject(Project $project, string $name, ?string $description = null): ProjectTemplate
    {
        $template = ProjectTemplate::create([
            'user_id' => $project->user_id,
            'name' => $name,
            'description' => $description,
            'billing_type' => $project->billing_type,
            'default_hourly_rate' => $project->hourly_rate,
            'default_currency' => $project->currency,
            'estimated_hours' => $project->estimated_hours,
        ]);

        // Copy tasks from the project
        $tasks = $project->tasks()->orderBy('sort_order')->get();

        foreach ($tasks as $index => $task) {
            ProjectTemplateTask::create([
                'template_id' => $template->id,
                'title' => $task->title,
                'description' => $task->description,
                'estimated_hours' => $task->estimated_hours,
                'priority' => $task->priority,
                'sort_order' => $index,
            ]);
        }

        return $template;
    }

    /**
     * Instantiate a new project from a template.
     *
     * @param  array{name?: string, client_id?: string, start_date?: string, deadline?: string}  $data
     */
    public function instantiate(ProjectTemplate $template, array $data, User $user): Project
    {
        $project = Project::create([
            'user_id' => $user->id,
            'client_id' => $data['client_id'],
            'reference' => ReferenceGenerator::generate('projects', 'PRJ'),
            'name' => $data['name'] ?? $template->name,
            'description' => $template->description,
            'billing_type' => $template->billing_type,
            'hourly_rate' => $template->default_hourly_rate,
            'currency' => $template->default_currency,
            'estimated_hours' => $template->estimated_hours,
            'start_date' => $data['start_date'] ?? null,
            'deadline' => $data['deadline'] ?? null,
            'status' => 'draft',
        ]);

        // Create tasks from template
        $templateTasks = $template->templateTasks()->ordered()->get();

        foreach ($templateTasks as $index => $templateTask) {
            Task::create([
                'project_id' => $project->id,
                'title' => $templateTask->title,
                'description' => $templateTask->description,
                'estimated_hours' => $templateTask->estimated_hours,
                'priority' => $templateTask->priority,
                'sort_order' => $index,
                'status' => 'todo',
            ]);
        }

        return $project;
    }
}
