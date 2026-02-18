<?php

use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function validExpensePayload(string $categoryId, ?string $projectId = null, ?string $clientId = null): array
{
    return [
        'expense_category_id' => $categoryId,
        'project_id' => $projectId,
        'client_id' => $clientId,
        'description' => 'Train ticket',
        'amount' => 35.50,
        'currency' => 'EUR',
        'tax_amount' => 7.10,
        'date' => now()->toDateString(),
        'payment_method' => 'card',
        'is_billable' => true,
        'is_reimbursable' => false,
        'vendor' => 'SNCF',
        'reference' => 'REF-123',
        'notes' => 'Customer visit',
        'status' => 'approved',
    ];
}

test('user can create read update filter and delete expenses with receipt management', function () {
    $user = User::factory()->create([
        'base_currency' => 'EUR',
    ]);
    $client = Client::factory()->create([
        'user_id' => $user->id,
    ]);
    $project = Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
    ]);
    $category = ExpenseCategory::factory()->create([
        'user_id' => $user->id,
    ]);

    $createResponse = $this->actingAs($user, 'sanctum')
        ->post('/api/v1/expenses', [
            ...validExpensePayload($category->id, $project->id, $client->id),
            'receipt' => UploadedFile::fake()->create('receipt.jpg', 50, 'image/jpeg'),
        ], ['Accept' => 'application/json']);

    $createResponse
        ->assertStatus(201)
        ->assertJsonPath('data.expense_category_id', $category->id);

    $expenseId = (string) $createResponse->json('data.id');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/expenses/'.$expenseId)
        ->assertStatus(200)
        ->assertJsonPath('data.id', $expenseId);

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/expenses/'.$expenseId, [
            ...validExpensePayload($category->id, $project->id, $client->id),
            'description' => 'Updated train ticket',
            'is_billable' => false,
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.description', 'Updated train ticket');

    $this->actingAs($user, 'sanctum')
        ->post('/api/v1/expenses/'.$expenseId.'/receipt', [
            'receipt' => UploadedFile::fake()->create('updated-receipt.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])
        ->assertStatus(200);

    $downloadResponse = $this->actingAs($user, 'sanctum')
        ->get('/api/v1/expenses/'.$expenseId.'/receipt', ['Accept' => 'application/json']);
    $downloadResponse->assertStatus(200);

    Expense::factory()->create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
        'project_id' => $project->id,
        'client_id' => $client->id,
        'description' => 'Software license',
        'amount' => 99,
        'is_billable' => true,
        'date' => now()->subDay()->toDateString(),
    ]);

    $filterResponse = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/expenses?expense_category_id='.$category->id.'&billable=1');
    $filterResponse->assertStatus(200);
    expect($filterResponse->json('data.data'))->toBeArray();

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/expenses/'.$expenseId)
        ->assertStatus(200);

    $this->assertDatabaseMissing('expenses', ['id' => $expenseId]);
});
