<?php

use App\Models\Client;
use App\Models\Contact;
use App\Models\DripSequence;
use App\Models\Segment;
use App\Models\SuppressedEmail;
use App\Models\User;
use App\Services\DripEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('drip enrollment service enrolls once and is idempotent', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
    ]);
    $sequence = DripSequence::factory()->create(['user_id' => $user->id]);

    $service = app(DripEnrollmentService::class);

    $first = $service->enroll($contact, $sequence);
    $second = $service->enroll($contact, $sequence);

    expect($first->id)->toBe($second->id);
    expect($sequence->enrollments()->count())->toBe(1);
});

test('drip enrollment service skips suppressed contacts', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
        'email' => 'blocked@drip.test',
    ]);
    $sequence = DripSequence::factory()->create(['user_id' => $user->id]);

    SuppressedEmail::query()->create([
        'user_id' => $user->id,
        'email' => 'blocked@drip.test',
        'reason' => 'manual',
        'suppressed_at' => now(),
    ]);

    $service = app(DripEnrollmentService::class);

    $enrollment = $service->enroll($contact, $sequence);

    expect($enrollment->status)->toBe('cancelled');
});

test('drip enrollment service enrolls all contacts from a segment', function () {
    $user = User::factory()->create();
    $segment = Segment::factory()->create([
        'user_id' => $user->id,
        'filters' => [
            'groups' => [
                [
                    'criteria' => [
                        ['type' => 'location', 'field' => 'city', 'operator' => 'equals', 'value' => 'Lyon'],
                    ],
                ],
            ],
        ],
    ]);

    $clientA = Client::factory()->create(['user_id' => $user->id, 'city' => 'Lyon']);
    $clientB = Client::factory()->create(['user_id' => $user->id, 'city' => 'Lyon']);
    Contact::factory()->create(['client_id' => $clientA->id]);
    Contact::factory()->create(['client_id' => $clientB->id]);

    $sequence = DripSequence::factory()->create(['user_id' => $user->id]);

    $count = app(DripEnrollmentService::class)->enrollSegment($segment, $sequence);

    expect($count)->toBe(2);
});
