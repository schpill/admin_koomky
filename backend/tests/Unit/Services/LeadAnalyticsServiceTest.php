<?php

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create(['base_currency' => 'EUR']);
    $this->actingAs($this->user, 'sanctum');
});

test('lead analytics computes total pipeline value', function () {
    \App\Models\Lead::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'new',
        'estimated_value' => 10000,
    ]);
    \App\Models\Lead::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'proposal_sent',
        'estimated_value' => 20000,
    ]);
    \App\Models\Lead::factory()->won()->create([
        'user_id' => $this->user->id,
        'estimated_value' => 15000,
    ]);

    $service = new \App\Services\LeadAnalyticsService;
    $analytics = $service->build($this->user);

    expect($analytics['total_pipeline_value'])->toBe(30000.00);
});

test('lead analytics computes leads by status', function () {
    \App\Models\Lead::factory()->newLead()->create(['user_id' => $this->user->id]);
    \App\Models\Lead::factory()->newLead()->create(['user_id' => $this->user->id]);
    \App\Models\Lead::factory()->won()->create(['user_id' => $this->user->id]);

    $service = new \App\Services\LeadAnalyticsService;
    $analytics = $service->build($this->user);

    expect($analytics['leads_by_status']['new'])->toBe(2)
        ->and($analytics['leads_by_status']['won'])->toBe(1);
});

test('lead analytics computes win rate', function () {
    \App\Models\Lead::factory()->won()->create([
        'user_id' => $this->user->id,
        'converted_at' => now(),
    ]);
    \App\Models\Lead::factory()->won()->create([
        'user_id' => $this->user->id,
        'converted_at' => now(),
    ]);
    \App\Models\Lead::factory()->lost()->create([
        'user_id' => $this->user->id,
        'updated_at' => now(),
    ]);

    $service = new \App\Services\LeadAnalyticsService;
    $analytics = $service->build($this->user);

    expect($analytics['win_rate'])->toBe(66.67);
});

test('lead analytics computes average deal value', function () {
    \App\Models\Lead::factory()->won()->create([
        'user_id' => $this->user->id,
        'estimated_value' => 10000,
        'converted_at' => now(),
    ]);
    \App\Models\Lead::factory()->won()->create([
        'user_id' => $this->user->id,
        'estimated_value' => 20000,
        'converted_at' => now(),
    ]);

    $service = new \App\Services\LeadAnalyticsService;
    $analytics = $service->build($this->user);

    expect($analytics['average_deal_value'])->toBe(15000.00);
});

test('lead analytics computes average time to close', function () {
    \App\Models\Lead::factory()->won()->create([
        'user_id' => $this->user->id,
        'created_at' => now()->subDays(30),
        'converted_at' => now(),
    ]);
    \App\Models\Lead::factory()->won()->create([
        'user_id' => $this->user->id,
        'created_at' => now()->subDays(60),
        'converted_at' => now(),
    ]);

    $service = new \App\Services\LeadAnalyticsService;
    $analytics = $service->build($this->user);

    expect($analytics['average_time_to_close'])->toBe(45.0);
});

test('lead analytics computes pipeline by source', function () {
    \App\Models\Lead::factory()->create([
        'user_id' => $this->user->id,
        'source' => 'website',
        'estimated_value' => 10000,
    ]);
    \App\Models\Lead::factory()->create([
        'user_id' => $this->user->id,
        'source' => 'referral',
        'estimated_value' => 20000,
    ]);

    $service = new \App\Services\LeadAnalyticsService;
    $analytics = $service->build($this->user);

    expect($analytics['pipeline_by_source'])->toHaveCount(2);
});

test('lead analytics handles empty pipeline', function () {
    $service = new \App\Services\LeadAnalyticsService;
    $analytics = $service->build($this->user);

    expect($analytics['total_pipeline_value'])->toBe(0.00)
        ->and($analytics['win_rate'])->toBe(0.00)
        ->and($analytics['average_deal_value'])->toBe(0.00);
});
