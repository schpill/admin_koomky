<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DripSequenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->sentence(2),
            'trigger_event' => 'manual',
            'trigger_campaign_id' => null,
            'status' => 'active',
            'settings' => null,
        ];
    }
}
