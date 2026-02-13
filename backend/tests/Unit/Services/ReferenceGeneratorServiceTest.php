<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\User;
use App\Services\ReferenceGeneratorService;

beforeEach(function () {
    $this->service = new ReferenceGeneratorService();
});

it('generates a valid UUID', function () {
    $uuid = $this->service->generateUuid();

    expect($uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

it('generates unique UUIDs', function () {
    $uuid1 = $this->service->generateUuid();
    $uuid2 = $this->service->generateUuid();

    expect($uuid1)->not->toBe($uuid2);
});

it('generates a client reference with correct format', function () {
    $user = User::factory()->create();
    $date = now()->format('Ymd');

    $reference = $this->service->generateClientReference($user);

    expect($reference)->toMatch("/^CLI-{$date}-\\d{4}$/");
});

it('generates sequential client references for same user on same day', function () {
    $user = User::factory()->create();

    $ref1 = $this->service->generateClientReference($user);

    // Create a client to increment the count
    Client::factory()->create(['user_id' => $user->id]);

    $ref2 = $this->service->generateClientReference($user);

    // Extract sequence numbers
    $seq1 = (int) substr($ref1, -4);
    $seq2 = (int) substr($ref2, -4);

    expect($seq2)->toBe($seq1 + 1);
});

// Note: Project, Invoice, and Quote reference tests are deferred to Phase 2
// when those tables/models will be created.
