<?php

use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('full export returns zip containing complete json archive', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create(['name' => 'Export Client']);
    Contact::factory()->for($client)->create(['first_name' => 'Alice']);

    $response = $this->actingAs($user, 'sanctum')
        ->get('/api/v1/export/full');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/zip');

    $content = $response->streamedContent();
    $archivePath = tempnam(sys_get_temp_dir(), 'koomky-export-');
    file_put_contents($archivePath, $content);

    $zip = new ZipArchive;
    $opened = $zip->open($archivePath);

    expect($opened)->toBeTrue();

    $json = $zip->getFromName('export.json');
    $zip->close();

    expect($json)->not->toBeFalse();

    $payload = json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);

    expect($payload['clients'][0]['name'] ?? null)->toBe('Export Client');
    expect($payload['contacts'][0]['first_name'] ?? null)->toBe('Alice');
});
