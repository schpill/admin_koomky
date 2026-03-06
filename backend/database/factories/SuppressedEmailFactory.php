<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SuppressedEmailFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'email' => $this->faker->unique()->safeEmail(),
            'reason' => 'manual',
            'source_campaign_id' => Campaign::factory(),
            'suppressed_at' => now(),
        ];
    }
}
