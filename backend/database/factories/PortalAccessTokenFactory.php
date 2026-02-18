<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\PortalAccessToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PortalAccessToken>
 */
class PortalAccessTokenFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'token' => PortalAccessToken::generateToken(),
            'email' => $this->faker->safeEmail(),
            'expires_at' => now()->addDays(7),
            'last_used_at' => null,
            'is_active' => true,
            'created_by_user_id' => User::factory(),
        ];
    }
}
