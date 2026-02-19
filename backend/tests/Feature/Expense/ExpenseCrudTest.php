<?php

use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

beforeEach(function() {
    Storage::fake('receipts');
    $this->user = User::factory()->create(['base_currency' => 'EUR']);
    $this->client = Client::factory()->create(['user_id' => $this->user->id]);
    $this->project = Project::factory()->create(['user_id' => $this->user->id, 'client_id' => $this->client->id]);
    $this->category = ExpenseCategory::factory()->create(['user_id' => $this->user->id]);
    
    $this->actingAs($this->user, 'sanctum');
});

test('can create an expense with a receipt', function () {
    $payload = validExpensePayload($this->category->id, $this->project->id, $this->client->id);
    
    $response = $this->postJson('/api/v1/expenses', [
        ...$payload,
        'receipt' => UploadedFile::fake()->create('receipt.jpg', 50, 'image/jpeg'),
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.description', 'Train ticket')
        ->assertJsonPath('data.receipt_filename', 'receipt.jpg');

    $this->assertDatabaseHas('expenses', ['description' => 'Train ticket']);
    
    $expense = Expense::first();
    Storage::disk('receipts')->assertExists($expense->receipt_path);
});

test('can view a single expense', function () {
    $expense = Expense::factory()->create(['user_id' => $this->user->id]);
    
    $this->getJson('/api/v1/expenses/'.$expense->id)
        ->assertStatus(200)
        ->assertJsonPath('data.id', $expense->id);
});

test('can update an expense', function () {
    $expense = Expense::factory()->create(['user_id' => $this->user->id, 'description' => 'Old description']);
    $payload = validExpensePayload($this->category->id);

    $this->putJson('/api/v1/expenses/'.$expense->id, [
        ...$payload,
        'description' => 'New description',
        'is_billable' => false,
    ])
    ->assertStatus(200)
    ->assertJsonPath('data.description', 'New description')
    ->assertJsonPath('data.is_billable', false);

    $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'description' => 'New description']);
});

test('can delete an expense and its receipt', function () {
    $expense = Expense::factory()->create(['user_id' => $this->user->id]);
    
    // Simulate a receipt file
    $path = 'receipts/' . uniqid() . '.jpg';
    Storage::disk('receipts')->put($path, 'dummy content');
    $expense->update(['receipt_path' => $path]);

    $this->deleteJson('/api/v1/expenses/'.$expense->id)
        ->assertStatus(200);

    $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    Storage::disk('receipts')->assertMissing($path);
});

test('can filter expenses by category and billable status', function () {
    Expense::factory()->create([
        'user_id' => $this->user->id,
        'expense_category_id' => $this->category->id,
        'is_billable' => true,
        'description' => 'Billable Expense',
    ]);
    Expense::factory()->create([
        'user_id' => $this->user->id,
        'expense_category_id' => $this->category->id,
        'is_billable' => false,
        'description' => 'Non-billable Expense',
    ]);
     Expense::factory()->create([
        'user_id' => $this->user->id,
        'is_billable' => true,
        'description' => 'Other Category Billable',
    ]);

    $response = $this->getJson('/api/v1/expenses?expense_category_id='.$this->category->id.'&billable=1');
    
    $response->assertStatus(200)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.description', 'Billable Expense');
});

test('receipt upload fails if file is too large', function() {
    $expense = Expense::factory()->create(['user_id' => $this->user->id]);
    
    // 11MB file, where limit is 10MB (10240 KB)
    $largeFile = UploadedFile::fake()->create('large-file.pdf', 11 * 1024);

    $this->postJson('/api/v1/expenses/'.$expense->id.'/receipt', [
        'receipt' => $largeFile,
    ])
    ->assertStatus(422)
    ->assertJsonValidationErrors('receipt');
});
