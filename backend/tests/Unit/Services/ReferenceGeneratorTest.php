<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Services\ReferenceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('reference generator supports project prefix', function () {
    $reference = ReferenceGenerator::generate('projects', 'PRJ');

    expect($reference)->toBe('PRJ-'.date('Y').'-0001');
});

test('reference generator increments existing project references', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'reference' => 'PRJ-'.date('Y').'-0009',
    ]);

    $reference = ReferenceGenerator::generate('projects', 'PRJ');

    expect($reference)->toBe('PRJ-'.date('Y').'-0010');
});

test('reference generator keeps client prefix behavior unchanged', function () {
    $reference = ReferenceGenerator::generate('clients', 'CLI');

    expect($reference)->toMatch('/^CLI-\d{4}-\d{4}$/');
});

test('reference generator does not reuse deleted latest invoice number', function () {
    $invoiceA = Invoice::factory()->create(['number' => 'FAC-'.date('Y').'-0001']);
    $invoiceB = Invoice::factory()->create(['number' => 'FAC-'.date('Y').'-0002']);

    $invoiceB->delete();

    $reference = ReferenceGenerator::generate('invoices', 'FAC');

    expect($reference)->toBe('FAC-'.date('Y').'-0003');
    expect($invoiceA->id)->not()->toBeNull();
});
