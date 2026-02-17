<?php

use App\Models\CampaignTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can create update and delete campaign template', function () {
    $user = User::factory()->create();

    $create = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/campaign-templates', [
            'name' => 'Welcome Template',
            'type' => 'email',
            'subject' => 'Welcome',
            'content' => '<p>Welcome</p>',
        ]);

    $create->assertStatus(201)
        ->assertJsonPath('data.name', 'Welcome Template');

    $templateId = (string) $create->json('data.id');

    $index = $this->actingAs($user, 'sanctum')->getJson('/api/v1/campaign-templates');
    $index->assertStatus(200)->assertJsonCount(1, 'data');

    $update = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/campaign-templates/'.$templateId, [
            'name' => 'Updated Template',
            'type' => 'email',
            'subject' => 'Updated',
            'content' => '<p>Updated</p>',
        ]);

    $update->assertStatus(200)
        ->assertJsonPath('data.name', 'Updated Template');

    $campaignTemplate = CampaignTemplate::findOrFail($templateId);

    $delete = $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/campaign-templates/'.$campaignTemplate->id);

    $delete->assertStatus(200);
});
