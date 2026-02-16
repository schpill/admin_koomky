<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can get and update invoicing settings', function () {
    $user = User::factory()->create([
        'payment_terms_days' => 30,
        'invoice_footer' => 'Default footer',
        'invoice_numbering_pattern' => 'FAC-YYYY-NNNN',
    ]);

    $getResponse = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/settings/invoicing');

    $getResponse->assertStatus(200)
        ->assertJsonPath('data.payment_terms_days', 30)
        ->assertJsonPath('data.invoice_numbering_pattern', 'FAC-YYYY-NNNN');

    $updateResponse = $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/settings/invoicing', [
            'payment_terms_days' => 45,
            'bank_details' => 'IBAN FR761234567890',
            'invoice_footer' => 'Updated footer',
            'invoice_numbering_pattern' => 'FAC-YYYY-NNNN',
        ]);

    $updateResponse->assertStatus(200)
        ->assertJsonPath('data.payment_terms_days', 45)
        ->assertJsonPath('data.invoice_footer', 'Updated footer');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'payment_terms_days' => 45,
        'invoice_footer' => 'Updated footer',
    ]);
});
