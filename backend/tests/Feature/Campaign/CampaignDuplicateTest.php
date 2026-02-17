<?php

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('duplicate endpoint clones campaign with draft status', function () {
    $user = User::factory()->create();

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'name' => 'Original Campaign',
        'status' => 'sent',
        'content' => 'Body',
        'subject' => 'Subject',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/campaigns/'.$campaign->id.'/duplicate');

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.content', 'Body');

    expect($response->json('data.name'))->toContain('Copy');
});
