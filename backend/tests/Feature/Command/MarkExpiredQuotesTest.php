<?php

use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('mark expired command updates only eligible quotes', function () {
    $sentExpired = Quote::factory()->create([
        'status' => 'sent',
        'valid_until' => now()->subDay()->toDateString(),
    ]);

    $sentStillValid = Quote::factory()->create([
        'status' => 'sent',
        'valid_until' => now()->addDay()->toDateString(),
    ]);

    $acceptedExpired = Quote::factory()->create([
        'status' => 'accepted',
        'valid_until' => now()->subDay()->toDateString(),
    ]);

    $this->artisan('quotes:mark-expired')
        ->assertExitCode(0);

    expect(Quote::findOrFail($sentExpired->id)->status)->toBe('expired');
    expect(Quote::findOrFail($sentStillValid->id)->status)->toBe('sent');
    expect(Quote::findOrFail($acceptedExpired->id)->status)->toBe('accepted');
});
