<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

test('filters clients by prospect status industry and department', function () {
    $user = User::factory()->create();

    $prospect = Client::factory()->create([
        'user_id' => $user->id,
        'status' => 'prospect',
        'industry' => 'Wedding Planner',
        'department' => '60',
    ]);

    Client::factory()->create([
        'user_id' => $user->id,
        'status' => 'active',
        'industry' => 'Photographer',
        'department' => '75',
    ]);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/clients?status=prospect');
    $response->assertOk()->assertJsonCount(1, 'data.data')->assertJsonPath('data.data.0.id', $prospect->id);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/clients?industry=Wedding%20Planner');
    $response->assertOk()->assertJsonCount(1, 'data.data')->assertJsonPath('data.data.0.id', $prospect->id);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/clients?department=60');
    $response->assertOk()->assertJsonCount(1, 'data.data')->assertJsonPath('data.data.0.id', $prospect->id);
});

test('prospect meta endpoints return industries and 101 departments', function () {
    $user = User::factory()->create();

    Client::factory()->create([
        'user_id' => $user->id,
        'industry' => 'Wedding Planner',
    ]);

    $industries = $this->actingAs($user, 'sanctum')->getJson('/api/v1/prospects/industries');
    $industries->assertOk()->assertJsonPath('data.0', 'Wedding Planner');

    $departments = $this->actingAs($user, 'sanctum')->getJson('/api/v1/prospects/departments');
    $departments->assertOk()->assertJsonCount(101, 'data');
});

test('can create client with industry department and prospect status', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/clients', [
        'name' => 'Nouveau Prospect',
        'industry' => 'Wedding Planner',
        'department' => '60',
        'status' => 'prospect',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'prospect')
        ->assertJsonPath('data.industry', 'Wedding Planner')
        ->assertJsonPath('data.department', '60');
});
