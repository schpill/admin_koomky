<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\PortalSettings>
 */
class PortalSettingsFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'portal_enabled' => true,
            'custom_logo' => null,
            'custom_color' => '#1F4F8A',
            'welcome_message' => $this->faker->sentence(),
            'payment_enabled' => false,
            'quote_acceptance_enabled' => true,
            'stripe_publishable_key' => null,
            'stripe_secret_key' => null,
            'stripe_webhook_secret' => null,
            'payment_methods_enabled' => ['card'],
        ];
    }
}
