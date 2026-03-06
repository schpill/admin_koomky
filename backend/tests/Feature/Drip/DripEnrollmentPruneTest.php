<?php

use App\Models\Client;
use App\Models\Contact;
use App\Models\DripEnrollment;
use App\Models\DripSequence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('prune command removes old completed and cancelled drip enrollments only', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
    ]);
    $sequence = DripSequence::factory()->create(['user_id' => $user->id]);

    $oldCompleted = DripEnrollment::factory()->create([
        'sequence_id' => $sequence->id,
        'contact_id' => $contact->id,
        'status' => 'completed',
        'completed_at' => now()->subDays(120),
        'updated_at' => now()->subDays(120),
    ]);

    $recentCompleted = DripEnrollment::factory()->create([
        'sequence_id' => $sequence->id,
        'contact_id' => Contact::factory()->create([
            'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
        ])->id,
        'status' => 'completed',
        'completed_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ]);

    $active = DripEnrollment::factory()->create([
        'sequence_id' => $sequence->id,
        'contact_id' => Contact::factory()->create([
            'client_id' => Client::factory()->create(['user_id' => $user->id])->id,
        ])->id,
        'status' => 'active',
    ]);

    $this->artisan('drip-enrollments:prune')->assertExitCode(0);

    $this->assertDatabaseMissing('drip_enrollments', ['id' => $oldCompleted->id]);
    $this->assertDatabaseHas('drip_enrollments', ['id' => $recentCompleted->id]);
    $this->assertDatabaseHas('drip_enrollments', ['id' => $active->id]);
});
