<?php

use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validQuotePayload(string $clientId): array
{
    return [
        'client_id' => $clientId,
        'issue_date' => now()->toDateString(),
        'currency' => 'EUR',
        'notes' => 'Website redesign quote',
        'discount_type' => 'percentage',
        'discount_value' => 5,
        'line_items' => [
            [
                'description' => 'Design package',
                'quantity' => 2,
                'unit_price' => 150,
                'vat_rate' => 20,
            ],
            [
                'description' => 'Development package',
                'quantity' => 5,
                'unit_price' => 100,
                'vat_rate' => 10,
            ],
        ],
    ];
}

test('user can create read update and delete draft quote', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $create = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/quotes', validQuotePayload($client->id));

    $create->assertStatus(201)
        ->assertJsonPath('data.client_id', $client->id)
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.number', fn (string $number): bool => str_starts_with($number, 'DEV-'));

    $quoteId = (string) $create->json('data.id');

    $this->assertDatabaseHas('quotes', ['id' => $quoteId]);
    $this->assertDatabaseCount('line_items', 2);

    $show = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/quotes/'.$quoteId);

    $show->assertStatus(200)
        ->assertJsonPath('data.id', $quoteId);

    $update = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/quotes/'.$quoteId, [
            ...validQuotePayload($client->id),
            'notes' => 'Updated quote note',
        ]);

    $update->assertStatus(200)
        ->assertJsonPath('data.notes', 'Updated quote note');

    $delete = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/quotes/'.$quoteId);

    $delete->assertStatus(200);
    $this->assertDatabaseMissing('quotes', ['id' => $quoteId]);
});

test('quote index supports filtering sorting and pagination', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $client = Client::factory()->create(['user_id' => $user->id]);

    Quote::factory()->count(2)->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'draft',
        'issue_date' => now()->subDays(2)->toDateString(),
    ]);

    Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
        'issue_date' => now()->toDateString(),
    ]);

    Quote::factory()->create([
        'user_id' => $other->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/quotes?status=draft&client_id='.$client->id.'&sort_by=issue_date&sort_order=asc&per_page=1');

    $response->assertStatus(200)
        ->assertJsonPath('data.current_page', 1)
        ->assertJsonPath('data.per_page', 1)
        ->assertJsonCount(1, 'data.data');
});

test('non draft quote cannot be updated or deleted', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $quote = Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'sent',
    ]);

    $update = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/quotes/'.$quote->id, [
            ...validQuotePayload($client->id),
            'notes' => 'Should fail',
        ]);

    $update->assertStatus(422)
        ->assertJsonPath('message', 'Only draft quotes can be updated');

    $delete = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/quotes/'.$quote->id);

    $delete->assertStatus(422)
        ->assertJsonPath('message', 'Only draft quotes can be deleted');
});
