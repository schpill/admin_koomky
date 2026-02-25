<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RagUsageLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'question' => $this->faker->sentence(),
            'chunks_used' => [],
            'tokens_used' => $this->faker->numberBetween(50, 500),
            'latency_ms' => $this->faker->numberBetween(100, 2000),
            'created_at' => now(),
        ];
    }
}
