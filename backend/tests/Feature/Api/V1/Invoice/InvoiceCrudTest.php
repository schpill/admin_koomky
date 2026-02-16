<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validInvoicePayload(string $clientId, ?string $projectId = null): array
{
    return [
        'client_id' => $clientId,
        'project_id' => $projectId,
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'currency' => 'EUR',
        'notes' => 'Monthly invoice',
        'discount_type' => 'percentage',
        'discount_value' => 5,
        'line_items' => [
            [
                'description' => 'Design service',
                'quantity' => 2,
                'unit_price' => 150,
                'vat_rate' => 20,
            ],
            [
                'description' => 'Development service',
                'quantity' => 5,
                'unit_price' => 100,
                'vat_rate' => 10,
            ],
        ],
    ];
}

test('user can create read update and delete draft invoice', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);

    $create = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/invoices', validInvoicePayload($client->id, $project->id));

    $create->assertStatus(201)
        ->assertJsonPath('data.client_id', $client->id)
        ->assertJsonPath('data.status', 'draft');

    $invoiceId = (string) $create->json('data.id');

    $this->assertDatabaseHas('invoices', ['id' => $invoiceId]);
    $this->assertDatabaseCount('line_items', 2);

    $show = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/invoices/'.$invoiceId);

    $show->assertStatus(200)
        ->assertJsonPath('data.id', $invoiceId);

    $update = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/invoices/'.$invoiceId, [
            ...validInvoicePayload($client->id, $project->id),
            'notes' => 'Updated note',
        ]);

    $update->assertStatus(200)
        ->assertJsonPath('data.notes', 'Updated note');

    $delete = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/invoices/'.$invoiceId);

    $delete->assertStatus(200);
    $this->assertDatabaseMissing('invoices', ['id' => $invoiceId]);
});

test('invoice index supports filtering sorting and pagination', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $client = Client::factory()->create(['user_id' => $user->id]);

    Invoice::factory()->count(2)->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'draft',
        'issue_date' => now()->subDays(2)->toDateString(),
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => now()->toDateString(),
    ]);

    Invoice::factory()->create([
        'user_id' => $other->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/invoices?status=draft&client_id='.$client->id.'&sort_by=issue_date&sort_order=asc&per_page=1');

    $response->assertStatus(200)
        ->assertJsonPath('data.current_page', 1)
        ->assertJsonPath('data.per_page', 1)
        ->assertJsonCount(1, 'data.data');
});

test('non draft invoice cannot be updated or deleted', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
    ]);

    $update = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/invoices/'.$invoice->id, [
            ...validInvoicePayload($client->id),
            'notes' => 'Should fail',
        ]);

    $update->assertStatus(422)
        ->assertJsonPath('message', 'Only draft invoices can be updated');

    $delete = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/invoices/'.$invoice->id);

    $delete->assertStatus(422)
        ->assertJsonPath('message', 'Only draft invoices can be deleted');
});
