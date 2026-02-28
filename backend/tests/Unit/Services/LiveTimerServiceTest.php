<?php

namespace Tests\Unit\Services;

use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\LiveTimerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveTimerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LiveTimerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LiveTimerService;
    }

    public function test_start_creates_time_entry_with_running_flag(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        $entry = $this->service->start($user, $task);

        $this->assertTrue($entry->is_running);
        $this->assertNotNull($entry->started_at);
        $this->assertEquals($user->id, $entry->user_id);
        $this->assertEquals($task->id, $entry->task_id);
    }

    public function test_start_with_description(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        $entry = $this->service->start($user, $task, 'Working on feature X');

        $this->assertEquals('Working on feature X', $entry->description);
    }

    public function test_start_stops_existing_timer(): void
    {
        $user = User::factory()->create();
        $task1 = Task::factory()->create();
        $task2 = Task::factory()->create();

        // Start first timer
        $entry1 = $this->service->start($user, $task1);
        $this->assertTrue($entry1->is_running);

        // Start second timer - should stop first
        $entry2 = $this->service->start($user, $task2);
        $this->assertTrue($entry2->is_running);

        // First entry should be stopped
        $entry1->refresh();
        $this->assertFalse($entry1->is_running);
    }

    public function test_stop_calculates_duration_minutes(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();
        $startTime = Carbon::parse('2026-02-28 10:00:00');

        Carbon::setTestNow($startTime);
        $this->service->start($user, $task);
        Carbon::setTestNow($startTime->copy()->addMinutes(2));

        $stoppedEntry = $this->service->stop($user);

        $this->assertFalse($stoppedEntry->is_running);
        $this->assertGreaterThanOrEqual(1, $stoppedEntry->duration_minutes);

        Carbon::setTestNow();
    }

    public function test_stop_throws_exception_when_no_active_timer(): void
    {
        $user = User::factory()->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No active timer found for this user.');

        $this->service->stop($user);
    }

    public function test_cancel_deletes_active_timer(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        $entry = $this->service->start($user, $task);
        $this->assertDatabaseHas('time_entries', ['id' => $entry->id]);

        $this->service->cancel($user);

        $this->assertDatabaseMissing('time_entries', ['id' => $entry->id]);
    }

    public function test_active_returns_running_entry(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        $this->assertNull($this->service->active($user));

        $entry = $this->service->start($user, $task);
        $activeEntry = $this->service->active($user);

        $this->assertInstanceOf(TimeEntry::class, $activeEntry);
        $this->assertSame($entry->id, $activeEntry->id);
    }

    public function test_active_returns_null_when_no_running_timer(): void
    {
        $user = User::factory()->create();

        $this->assertSame(null, $this->service->active($user));
    }

    public function test_timer_isolation_between_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $task1 = Task::factory()->create();
        $task2 = Task::factory()->create();

        // User 1 starts timer
        $entry1 = $this->service->start($user1, $task1);
        $this->assertTrue($entry1->is_running);

        // User 2 starts timer - should not affect user 1
        $entry2 = $this->service->start($user2, $task2);
        $this->assertTrue($entry2->is_running);

        // Both should be running
        $this->assertNotNull($this->service->active($user1));
        $this->assertNotNull($this->service->active($user2));

        // User 1 stops - user 2 should still be running
        $this->service->stop($user1);
        $this->assertNull($this->service->active($user1));
        $this->assertNotNull($this->service->active($user2));
    }
}
