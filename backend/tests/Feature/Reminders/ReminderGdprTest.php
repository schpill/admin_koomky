<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\ReminderDelivery;
use App\Models\ReminderSequence;
use App\Models\User;
use App\Services\DataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

test('gdpr export includes reminder_deliveries csv and isolates by user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $client = Client::factory()->create(['user_id' => $user->id]);
    $sequence = ReminderSequence::factory()->withSteps()->create(['user_id' => $user->id]);
    $step = $sequence->steps()->firstOrFail();
    $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);

    ReminderDelivery::query()->create([
        'invoice_id' => $invoice->id,
        'reminder_step_id' => $step->id,
        'user_id' => $user->id,
        'sent_at' => now(),
        'status' => 'sent',
    ]);

    $otherClient = Client::factory()->create(['user_id' => $other->id]);
    $otherSequence = ReminderSequence::factory()->withSteps()->create(['user_id' => $other->id]);
    $otherStep = $otherSequence->steps()->firstOrFail();
    $otherInvoice = Invoice::factory()->create(['user_id' => $other->id, 'client_id' => $otherClient->id]);
    ReminderDelivery::query()->create([
        'invoice_id' => $otherInvoice->id,
        'reminder_step_id' => $otherStep->id,
        'user_id' => $other->id,
        'sent_at' => now(),
        'status' => 'sent',
    ]);

    $service = app(DataExportService::class);
    $archivePath = $service->createArchive($user);

    $zip = new \ZipArchive;
    expect($zip->open($archivePath))->toBeTrue();

    $csv = $zip->getFromName('reminder_deliveries.csv');
    $zip->close();

    expect($csv)->not()->toBeFalse();
    expect((string) $csv)->toContain((string) $invoice->number);
    expect((string) $csv)->not()->toContain((string) $otherInvoice->number);

    @unlink($archivePath);
});
