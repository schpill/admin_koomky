<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignLinkClick;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignLinkClick>
 */
class CampaignLinkClickFactory extends Factory
{
    protected $model = CampaignLinkClick::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'campaign_id' => Campaign::factory(),
            'recipient_id' => CampaignRecipient::factory(),
            'contact_id' => Contact::factory(),
            'url' => 'https://example.com/'.$this->faker->slug(),
            'clicked_at' => now(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => 'Mozilla/5.0',
        ];
    }
}
