<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TicketMessage>
 */
class TicketMessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TicketMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create();

        return [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'content' => $this->faker->paragraph,
            'is_internal' => $this->faker->boolean,
        ];
    }

    public function public(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'is_internal' => false,
        ]);
    }

    public function internal(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'is_internal' => true,
        ]);
    }
}
