<?php

namespace Database\Factories;

use App\Models\CalendarConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $now = now();
        $startAt = $now->copy()->addDays(random_int(0, 20))->setTime(random_int(8, 17), 0);
        $typeOptions = ['meeting', 'deadline', 'reminder', 'task', 'custom'];
        $syncStatuses = ['local', 'synced', 'conflict'];

        return [
            'user_id' => User::factory(),
            'calendar_connection_id' => CalendarConnection::factory(),
            'external_id' => 'evt_'.random_int(1000, 9999),
            'title' => 'Calendar event '.random_int(10, 99),
            'description' => 'Generated event description',
            'start_at' => $startAt,
            'end_at' => $startAt->copy()->addHour(),
            'all_day' => false,
            'location' => 'Remote',
            'type' => $typeOptions[random_int(0, count($typeOptions) - 1)],
            'eventable_type' => null,
            'eventable_id' => null,
            'recurrence_rule' => null,
            'sync_status' => $syncStatuses[random_int(0, count($syncStatuses) - 1)],
            'external_updated_at' => $now->copy()->subMinutes(random_int(1, 120)),
        ];
    }
}
