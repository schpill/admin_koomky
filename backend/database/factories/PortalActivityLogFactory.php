<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\PortalAccessToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\PortalActivityLog>
 */
class PortalActivityLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'portal_access_token_id' => PortalAccessToken::factory(),
            'action' => $this->faker->randomElement([
                'login',
                'logout',
                'view_dashboard',
                'view_invoice',
                'view_quote',
                'download_pdf',
                'accept_quote',
                'reject_quote',
            ]),
            'entity_type' => null,
            'entity_id' => null,
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }
}
