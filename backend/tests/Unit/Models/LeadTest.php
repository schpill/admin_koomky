<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create(['base_currency' => 'EUR']);
    $this->actingAs($this->user, 'sanctum');
});

test('lead factory creates valid lead', function () {
    $lead = \App\Models\Lead::factory()->create(['user_id' => $this->user->id]);

    expect($lead->user_id)->toBe($this->user->id)
        ->and($lead->status)->toBeIn(['new', 'contacted', 'qualified', 'proposal_sent', 'negotiating']);
});

test('lead has won state', function () {
    $lead = \App\Models\Lead::factory()->won()->create(['user_id' => $this->user->id]);

    expect($lead->status)->toBe('won')
        ->and($lead->probability)->toBe(100)
        ->and($lead->converted_at)->not->toBeNull();
});

test('lead has lost state', function () {
    $lead = \App\Models\Lead::factory()->lost()->create(['user_id' => $this->user->id]);

    expect($lead->status)->toBe('lost')
        ->and($lead->probability)->toBe(0)
        ->and($lead->lost_reason)->not->toBeNull();
});

test('lead can check if convertible', function () {
    $wonLead = \App\Models\Lead::factory()->won()->create(['user_id' => $this->user->id]);
    $newLead = \App\Models\Lead::factory()->newLead()->create(['user_id' => $this->user->id]);

    expect($wonLead->canConvert())->toBeTrue()
        ->and($newLead->canConvert())->toBeFalse();
});

test('lead already converted cannot convert again', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);
    $lead = \App\Models\Lead::factory()->converted()->create([
        'user_id' => $this->user->id,
        'won_client_id' => $client->id,
    ]);

    expect($lead->canConvert())->toBeFalse();
});

test('lead is terminal when won or lost', function () {
    $wonLead = \App\Models\Lead::factory()->won()->create(['user_id' => $this->user->id]);
    $lostLead = \App\Models\Lead::factory()->lost()->create(['user_id' => $this->user->id]);
    $newLead = \App\Models\Lead::factory()->newLead()->create(['user_id' => $this->user->id]);

    expect($wonLead->isTerminal())->toBeTrue()
        ->and($lostLead->isTerminal())->toBeTrue()
        ->and($newLead->isTerminal())->toBeFalse();
});

test('lead scopes work correctly', function () {
    \App\Models\Lead::factory()->newLead()->create(['user_id' => $this->user->id]);
    \App\Models\Lead::factory()->won()->create(['user_id' => $this->user->id]);
    \App\Models\Lead::factory()->lost()->create(['user_id' => $this->user->id]);

    $openDeals = \App\Models\Lead::openDeals()->where('user_id', $this->user->id)->count();
    $closedDeals = \App\Models\Lead::closedDeals()->where('user_id', $this->user->id)->count();

    expect($openDeals)->toBe(1)
        ->and($closedDeals)->toBe(2);
});

test('lead has activities relationship', function () {
    $lead = \App\Models\Lead::factory()->create(['user_id' => $this->user->id]);

    \App\Models\LeadActivity::factory()->create(['lead_id' => $lead->id]);

    expect($lead->activities)->toHaveCount(1);
});

test('lead has won client relationship', function () {
    $client = \App\Models\Client::factory()->create(['user_id' => $this->user->id]);
    $lead = \App\Models\Lead::factory()->converted()->create([
        'user_id' => $this->user->id,
        'won_client_id' => $client->id,
    ]);

    expect($lead->wonClient->id)->toBe($client->id);
});

test('lead searchable array includes key fields', function () {
    $lead = \App\Models\Lead::factory()->create([
        'user_id' => $this->user->id,
        'company_name' => 'Acme Corp',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@acme.com',
        'notes' => 'Important lead',
    ]);

    $searchable = $lead->toSearchableArray();

    expect($searchable)->toHaveKey('company_name')
        ->and($searchable)->toHaveKey('first_name')
        ->and($searchable)->toHaveKey('last_name')
        ->and($searchable)->toHaveKey('email')
        ->and($searchable)->toHaveKey('notes');
});
