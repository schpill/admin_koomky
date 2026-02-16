<?php

use App\Jobs\SendQuoteJob;
use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('sending quote dispatches queued job', function () {
    Queue::fake();

    $user = User::factory()->create();
    $client = Client::factory()->create([
        'user_id' => $user->id,
        'email' => 'billing@client.test',
    ]);

    $quote = Quote::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/quotes/'.$quote->id.'/send');

    $response->assertStatus(200);

    Queue::assertPushed(SendQuoteJob::class, function (SendQuoteJob $job) use ($quote) {
        return $job->quoteId === $quote->id;
    });
});
