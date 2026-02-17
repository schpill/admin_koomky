<?php

use App\Models\Campaign;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('metrics endpoint exposes prometheus format and custom application metrics', function () {
    $user = User::factory()->create();
    Invoice::factory()->count(2)->create(['user_id' => $user->id]);
    Campaign::factory()->create([
        'user_id' => $user->id,
        'status' => 'sent',
    ]);

    $response = $this->get('/metrics');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=UTF-8');

    $body = (string) $response->getContent();

    expect($body)->toContain('koomky_active_users_total');
    expect($body)->toContain('koomky_invoices_generated_total');
    expect($body)->toContain('koomky_campaigns_sent_total');
    expect($body)->toContain('koomky_queue_jobs_waiting');
    expect($body)->toContain('koomky_emails_sent_total');
});
