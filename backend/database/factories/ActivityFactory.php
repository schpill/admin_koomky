<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'type' => fake()->randomElement([
                Activity::TYPE_FINANCIAL,
                Activity::TYPE_PROJECT,
                Activity::TYPE_COMMUNICATION,
                Activity::TYPE_NOTE,
                Activity::TYPE_SYSTEM,
            ]),
            'description' => fake()->sentence(),
            'metadata' => null,
        ];
    }
}
