<?php

namespace Database\Factories;

use App\Models\Segment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['email', 'sms']);

        return [
            'user_id' => User::factory(),
            'segment_id' => Segment::factory(),
            'template_id' => null,
            'name' => $this->faker->sentence(3),
            'type' => $type,
            'status' => $this->faker->randomElement(['draft', 'scheduled', 'sending', 'sent']),
            'subject' => $type === 'email' ? $this->faker->sentence(4) : null,
            'content' => $this->faker->paragraph(),
            'scheduled_at' => null,
            'started_at' => null,
            'completed_at' => null,
            'settings' => [
                'throttle_rate_per_minute' => $type === 'email' ? 100 : 30,
            ],
        ];
    }
}
