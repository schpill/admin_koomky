<?php

namespace Database\Factories;

use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\WebhookDelivery>
 */
class WebhookDeliveryFactory extends Factory
{
    protected $model = WebhookDelivery::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'webhook_endpoint_id' => WebhookEndpoint::factory(),
            'event' => $this->faker->randomElement(['invoice.created', 'invoice.paid', 'lead.created']),
            'payload' => ['event' => 'invoice.created', 'data' => ['id' => '123']],
            'response_status' => null,
            'response_body' => null,
            'attempt_count' => 1,
            'delivered_at' => null,
            'failed_at' => null,
            'next_retry_at' => null,
            'created_at' => now(),
        ];
    }

    /**
     * Indicate that the delivery was successful.
     */
    public function delivered(): self
    {
        return $this->state(fn (): array => [
            'response_status' => 200,
            'response_body' => '{"success":true}',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Indicate that the delivery failed.
     */
    public function failed(): self
    {
        return $this->state(fn (): array => [
            'response_status' => 500,
            'response_body' => '{"error":"Internal Server Error"}',
            'failed_at' => now(),
        ]);
    }

    /**
     * Set the number of attempts.
     */
    public function withAttempts(int $count): self
    {
        return $this->state(fn (): array => [
            'attempt_count' => $count,
        ]);
    }
}
