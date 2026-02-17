<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CampaignRecipient>
 */
class CampaignRecipientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'contact_id' => Contact::factory(),
            'email' => $this->faker->safeEmail(),
            'phone' => '+33'.$this->faker->numerify('6########'),
            'status' => $this->faker->randomElement(['pending', 'sent', 'delivered']),
            'sent_at' => null,
            'delivered_at' => null,
            'opened_at' => null,
            'clicked_at' => null,
            'bounced_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
            'metadata' => null,
        ];
    }
}
