<?php

use App\Models\Campaign;
use App\Models\User;
use App\Services\SuppressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('suppression service is idempotent and can detect suppressed emails', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['user_id' => $user->id]);

    $service = app(SuppressionService::class);

    $service->suppress($user, 'blocked@example.test', 'manual', $campaign->id);
    $service->suppress($user, 'blocked@example.test', 'manual', $campaign->id);

    expect($service->isSuppressed($user, 'blocked@example.test'))->toBeTrue();
    expect($service->getSuppressedEmails($user))->toHaveCount(1);

    $this->assertDatabaseHas('suppressed_emails', [
        'user_id' => $user->id,
        'email' => 'blocked@example.test',
        'reason' => 'manual',
        'source_campaign_id' => $campaign->id,
    ]);
});

test('suppression service can import and export csv entries', function () {
    $user = User::factory()->create();
    $service = app(SuppressionService::class);

    $csvPath = storage_path('framework/testing/suppression-import.csv');
    file_put_contents($csvPath, "email\nfirst@example.test\nsecond@example.test\nfirst@example.test\n");

    $result = $service->importCsv($user, $csvPath);

    expect($result)->toBe([
        'imported' => 2,
        'skipped' => 1,
    ]);

    $response = $service->exportCsv($user);
    $content = $response->getContent();

    expect($content)->toContain('first@example.test');
    expect($content)->toContain('second@example.test');
});
