<?php

use App\Models\Client;
use App\Models\Contact;
use App\Models\DripEnrollment;
use App\Models\DripSequence;
use App\Models\DripStep;
use App\Models\SuppressedEmail;
use App\Models\User;
use App\Services\DataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('gdpr export includes drip enrollments and suppressed emails', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
        'email' => 'gdpr@drip.test',
    ]);
    $sequence = DripSequence::factory()->create(['user_id' => $user->id]);
    DripStep::factory()->create([
        'sequence_id' => $sequence->id,
        'position' => 1,
    ]);
    $enrollment = DripEnrollment::factory()->create([
        'sequence_id' => $sequence->id,
        'contact_id' => $contact->id,
    ]);

    $suppressed = SuppressedEmail::query()->create([
        'user_id' => $user->id,
        'email' => 'gdpr@drip.test',
        'reason' => 'manual',
        'suppressed_at' => now(),
    ]);

    $export = app(DataExportService::class)->exportUserData($user);

    expect($export['drip_sequences'][0]['id'])->toBe($sequence->id);
    expect($export['drip_enrollments'][0]['id'])->toBe($enrollment->id);
    expect($export['suppressed_emails'][0]['id'])->toBe($suppressed->id);
});
