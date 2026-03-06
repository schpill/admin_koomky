<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CampaignVariant>
 */
class CampaignVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'label' => $this->faker->randomElement(['A', 'B']),
            'subject' => $this->faker->sentence(4),
            'content' => $this->faker->paragraph(),
            'send_percent' => 50,
            'sent_count' => 0,
            'open_count' => 0,
            'click_count' => 0,
        ];
    }
}
