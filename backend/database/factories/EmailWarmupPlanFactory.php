<?php

namespace Database\Factories;

use App\Models\EmailWarmupPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailWarmupPlan>
 */
class EmailWarmupPlanFactory extends Factory
{
    protected $model = EmailWarmupPlan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Warm-up plan',
            'status' => 'active',
            'daily_volume_start' => 20,
            'daily_volume_max' => 200,
            'increment_percent' => 30,
            'current_day' => 0,
            'current_daily_limit' => 20,
            'started_at' => now(),
        ];
    }
}
