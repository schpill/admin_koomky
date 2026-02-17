<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CampaignTemplate>
 */
class CampaignTemplateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->sentence(2),
            'subject' => $this->faker->sentence(4),
            'content' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['email', 'sms']),
        ];
    }
}
