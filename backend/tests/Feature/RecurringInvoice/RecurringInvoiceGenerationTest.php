<?php

use App\Jobs\GenerateRecurringInvoiceJob;
use App\Jobs\SendInvoiceJob;
use App\Models\RecurringInvoiceProfile;
use App\Models\User;
use App\Notifications\RecurringInvoiceGeneratedNotification;
use App\Services\RecurringInvoiceGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('command dispatches jobs only for active due profiles', function () {
    Queue::fake();

    $activeDue = RecurringInvoiceProfile::factory()->create([
        'status' => 'active',
        'next_due_date' => now()->toDateString(),
    ]);

    RecurringInvoiceProfile::factory()->create([
        'status' => 'paused',
        'next_due_date' => now()->toDateString(),
    ]);

    RecurringInvoiceProfile::factory()->create([
        'status' => 'cancelled',
        'next_due_date' => now()->toDateString(),
    ]);

    $this->artisan('invoices:generate-recurring')
        ->assertExitCode(0);

    Queue::assertPushed(GenerateRecurringInvoiceJob::class, function (GenerateRecurringInvoiceJob $job) use ($activeDue): bool {
        return $job->profileId === $activeDue->id;
    });
    Queue::assertPushed(GenerateRecurringInvoiceJob::class, 1);
});

test('generation job creates invoice, can auto send and dispatches notification', function () {
    Queue::fake();
    Notification::fake();

    $user = User::factory()->create();

    $profile = RecurringInvoiceProfile::factory()->create([
        'user_id' => $user->id,
        'status' => 'active',
        'next_due_date' => now()->toDateString(),
        'auto_send' => true,
    ]);

    $job = new GenerateRecurringInvoiceJob($profile->id);
    $job->handle(app(RecurringInvoiceGeneratorService::class));

    $profile->refresh();

    expect($profile->invoices()->count())->toBe(1);

    Queue::assertPushed(SendInvoiceJob::class);
    Notification::assertSentTo($user, RecurringInvoiceGeneratedNotification::class);
});
