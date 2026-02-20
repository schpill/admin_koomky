<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\WebhookEndpoint>
 */
class WebhookEndpointFactory extends Factory
{
    protected $model = WebhookEndpoint::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->company().' Webhook',
            'url' => 'https://'.$this->faker->domainName().'/webhook',
            'secret' => Str::random(32),
            'events' => ['invoice.created', 'invoice.paid'],
            'is_active' => true,
            'last_triggered_at' => null,
        ];
    }

    /**
     * Indicate that the endpoint is inactive.
     */
    public function inactive(): self
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Set specific events for the endpoint.
     *
     * @param  array<int, string>  $events
     */
    public function withEvents(array $events): self
    {
        return $this->state(fn (): array => [
            'events' => $events,
        ]);
    }

    /**
     * Indicate that the endpoint has been triggered.
     */
    public function triggered(): self
    {
        return $this->state(fn (): array => [
            'last_triggered_at' => now(),
        ]);
    }
}
