<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ExpenseCategory;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 5, 600);
        $taxAmount = round($amount * 0.2, 2);

        return [
            'user_id' => User::factory(),
            'expense_category_id' => ExpenseCategory::factory(),
            'project_id' => null,
            'client_id' => null,
            'description' => $this->faker->sentence(4),
            'amount' => $amount,
            'currency' => 'EUR',
            'base_currency_amount' => $amount,
            'tax_amount' => $taxAmount,
            'tax_rate' => 20,
            'date' => now()->toDateString(),
            'payment_method' => $this->faker->randomElement(['cash', 'card', 'bank_transfer', 'other']),
            'is_billable' => $this->faker->boolean(),
            'is_reimbursable' => $this->faker->boolean(),
            'reimbursed_at' => null,
            'vendor' => $this->faker->company(),
            'reference' => $this->faker->bothify('EXP-####'),
            'notes' => $this->faker->optional()->sentence(),
            'receipt_path' => null,
            'receipt_filename' => null,
            'receipt_mime_type' => null,
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
        ];
    }

    public function forProject(Project $project): self
    {
        return $this->state(fn (): array => [
            'user_id' => $project->user_id,
            'client_id' => $project->client_id,
            'project_id' => $project->id,
            'expense_category_id' => ExpenseCategory::factory()->state([
                'user_id' => $project->user_id,
            ]),
        ]);
    }

    public function forClient(Client $client): self
    {
        return $this->state(fn (): array => [
            'user_id' => $client->user_id,
            'client_id' => $client->id,
            'expense_category_id' => ExpenseCategory::factory()->state([
                'user_id' => $client->user_id,
            ]),
        ]);
    }
}
