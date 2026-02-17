<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CalendarConnection>
 */
class CalendarConnectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $providers = ['google', 'caldav'];
        $provider = $providers[random_int(0, count($providers) - 1)];

        $credentials = $provider === 'google'
            ? [
                'access_token' => 'google_token_'.random_int(1000, 9999),
                'refresh_token' => 'google_refresh_'.random_int(1000, 9999),
            ]
            : [
                'base_url' => 'https://caldav.example.test',
                'username' => 'user_'.random_int(100, 999),
                'password' => 'secret_'.random_int(100, 999),
            ];

        return [
            'user_id' => User::factory(),
            'provider' => $provider,
            'name' => ucfirst($provider).' connection',
            'credentials' => $credentials,
            'calendar_id' => $provider === 'google' ? 'primary' : 'personal',
            'sync_enabled' => true,
            'last_synced_at' => null,
        ];
    }
}
