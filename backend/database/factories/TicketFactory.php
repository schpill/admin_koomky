<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Ticket::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $owner = User::factory()->create();
        $client = Client::factory()->create();
        $project = Project::factory()->for($client)->create();

        return [
            'user_id' => $owner->id,
            'assigned_to' => null,
            'client_id' => $client->id,
            'project_id' => $project->id,
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'status' => $this->faker->randomElement(TicketStatus::cases()),
            'priority' => $this->faker->randomElement(TicketPriority::cases()),
            'category' => $this->faker->word,
            'tags' => [$this->faker->word, $this->faker->word],
            'deadline' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
        ];
    }

    public function withOwner(User $owner): Factory
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $owner->id,
        ]);
    }

    public function withAssignee(User $assignee): Factory
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => $assignee->id,
        ]);
    }

    public function withClient(Client $client): Factory
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->id,
        ]);
    }

    public function withProject(Project $project): Factory
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project->id,
        ]);
    }

    public function status(TicketStatus $status): Factory
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    public function priority(TicketPriority $priority): Factory
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
        ]);
    }

    public function overdue(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'deadline' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
            'status' => TicketStatus::Open,
        ]);
    }
}
