<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceReminderSchedule;
use App\Models\ReminderSequence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

test('invoice reminder controller flow works', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'status' => 'overdue']);
    $sequence = ReminderSequence::factory()->withSteps()->create(['user_id' => $user->id]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/invoices/'.$invoice->id.'/reminder/attach', ['sequence_id' => $sequence->id])
        ->assertStatus(200)
        ->assertJsonPath('data.sequence_id', $sequence->id);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/invoices/'.$invoice->id.'/reminder')
        ->assertStatus(200);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/invoices/'.$invoice->id.'/reminder/pause')
        ->assertStatus(200)
        ->assertJsonPath('data.is_paused', true);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/invoices/'.$invoice->id.'/reminder/resume')
        ->assertStatus(200)
        ->assertJsonPath('data.is_paused', false);

    $skip = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/invoices/'.$invoice->id.'/reminder/skip')
        ->assertStatus(200);

    $scheduleId = $skip->json('data.id');
    $schedule = InvoiceReminderSchedule::query()->findOrFail($scheduleId);
    expect($schedule->deliveries()->where('status', 'skipped')->exists())->toBeTrue();

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/invoices/'.$invoice->id.'/reminder')
        ->assertStatus(204);
});

test('invoice reminder ownership is enforced', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $invoice = Invoice::factory()->create([
        'user_id' => $owner->id,
        'client_id' => Client::factory()->create(['user_id' => $owner->id])->id,
    ]);

    $this->actingAs($other, 'sanctum')
        ->getJson('/api/v1/invoices/'.$invoice->id.'/reminder')
        ->assertStatus(403);
});
