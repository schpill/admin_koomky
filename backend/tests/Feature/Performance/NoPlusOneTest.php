<?php

use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('clients index stays within query budget with relations eager loaded', function () {
    $user = User::factory()->create();

    Client::factory()
        ->count(5)
        ->for($user)
        ->create()
        ->each(function (Client $client): void {
            Contact::factory()->count(2)->for($client)->create();
        });

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/clients?per_page=50')
        ->assertOk();

    $queryCount = count(DB::getQueryLog());

    expect($queryCount)->toBeLessThanOrEqual(8);
});
