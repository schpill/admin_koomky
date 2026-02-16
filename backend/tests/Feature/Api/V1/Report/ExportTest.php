<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('report export supports csv and pdf formats', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'status' => 'paid',
        'issue_date' => now()->toDateString(),
        'total' => 500,
    ]);

    $csv = $this->actingAs($user, 'sanctum')
        ->get('/api/v1/reports/export?type=revenue&format=csv');

    $csv->assertStatus(200);
    expect($csv->headers->get('content-type'))->toContain('text/csv');
    expect($csv->streamedContent())->toContain('month,total');

    $pdf = $this->actingAs($user, 'sanctum')
        ->get('/api/v1/reports/export?type=vat-summary&format=pdf');

    $pdf->assertStatus(200);
    expect($pdf->headers->get('content-type'))->toContain('application/pdf');
    expect(substr((string) $pdf->getContent(), 0, 4))->toBe('%PDF');
});
