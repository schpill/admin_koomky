<?php

namespace Tests\Feature\Timer;

use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveTimerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->task = Task::factory()->create();
    }

    public function test_get_active_returns_204_when_no_timer(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/timer/active');

        $response->assertStatus(204);
    }

    public function test_get_active_returns_timer_when_running(): void
    {
        $entry = TimeEntry::create([
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
            'is_running' => true,
            'started_at' => now(),
            'duration_minutes' => 0,
            'date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/timer/active');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'task_id',
                    'task_name',
                    'project_id',
                    'project_name',
                    'started_at',
                    'description',
                ],
            ]);
    }

    public function test_start_timer_creates_entry(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/timer/start', [
                'task_id' => $this->task->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'task_id',
                    'started_at',
                ],
            ]);

        $this->assertDatabaseHas('time_entries', [
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
            'is_running' => true,
        ]);
    }

    public function test_start_timer_with_description(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/timer/start', [
                'task_id' => $this->task->id,
                'description' => 'Working on feature X',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('time_entries', [
            'user_id' => $this->user->id,
            'description' => 'Working on feature X',
        ]);
    }

    public function test_start_timer_validates_task_exists(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/timer/start', [
                'task_id' => 'non-existent-id',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['task_id']);
    }

    public function test_stop_timer_returns_time_entry(): void
    {
        TimeEntry::create([
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
            'is_running' => true,
            'started_at' => now()->subMinutes(5),
            'duration_minutes' => 0,
            'date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/timer/stop');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'task_id',
                    'duration_minutes',
                    'date',
                ],
            ]);
    }

    public function test_stop_timer_returns_422_when_no_timer(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/timer/stop');

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'No active timer to stop.',
            ]);
    }

    public function test_cancel_timer_returns_204(): void
    {
        TimeEntry::create([
            'user_id' => $this->user->id,
            'task_id' => $this->task->id,
            'is_running' => true,
            'started_at' => now(),
            'duration_minutes' => 0,
            'date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/timer/cancel');

        $response->assertStatus(204);
        $this->assertDatabaseMissing('time_entries', [
            'user_id' => $this->user->id,
            'is_running' => true,
        ]);
    }

    public function test_timer_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/timer/active');

        $response->assertStatus(401);
    }
}