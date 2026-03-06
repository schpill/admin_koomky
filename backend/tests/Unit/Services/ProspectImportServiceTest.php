<?php

use App\Models\Client;
use App\Models\Contact;
use App\Models\ImportSession;
use App\Models\ImportSessionError;
use App\Models\Tag;
use App\Models\User;
use App\Services\ProspectImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Queue::fake();
});

function createImportCsv(string $filename, string $content): void
{
    Storage::disk('local')->put($filename, $content);
}

test('imports valid rows with prospect status and fields', function () {
    $user = User::factory()->create();

    createImportCsv('imports/prospects.csv', "Nom,Email,Téléphone,Département,Secteur,Prénom\nAcme,acme@example.com,0600000000,60,Wedding Planner,Jane\n");

    $session = ImportSession::factory()->create([
        'user_id' => $user->id,
        'filename' => 'imports/prospects.csv',
        'original_filename' => 'prospects.csv',
        'status' => 'mapping',
        'column_mapping' => [
            'Nom' => 'name',
            'Email' => 'email',
            'Téléphone' => 'phone',
            'Département' => 'department',
            'Secteur' => 'industry',
            'Prénom' => 'contact.first_name',
        ],
        'default_tags' => ['wedding-planner'],
        'options' => ['duplicate_strategy' => 'skip', 'default_status' => 'prospect'],
    ]);

    app(ProspectImportService::class)->import($session);

    $client = Client::query()->where('user_id', $user->id)->firstOrFail();
    expect($client->status)->toBe('prospect');
    expect($client->industry)->toBe('Wedding Planner');
    expect($client->department)->toBe('60');

    expect(Contact::query()->where('client_id', $client->id)->count())->toBe(1);
    expect(Tag::query()->where('user_id', $user->id)->where('name', 'wedding-planner')->exists())->toBeTrue();

    $session->refresh();
    expect($session->success_rows)->toBe(1);
    expect($session->error_rows)->toBe(0);
});

test('handles duplicate strategy skip and update and invalid row', function () {
    $user = User::factory()->create();

    $existing = Client::factory()->create([
        'user_id' => $user->id,
        'email' => 'dup@example.com',
        'name' => 'Legacy',
    ]);

    createImportCsv('imports/prospects.csv', "Nom,Email,Téléphone\nNew Name,dup@example.com,0611111111\nInvalid,not-an-email,0600000000\n");

    $sessionSkip = ImportSession::factory()->create([
        'user_id' => $user->id,
        'filename' => 'imports/prospects.csv',
        'original_filename' => 'prospects.csv',
        'status' => 'mapping',
        'column_mapping' => ['Nom' => 'name', 'Email' => 'email', 'Téléphone' => 'phone'],
        'options' => ['duplicate_strategy' => 'skip', 'default_status' => 'prospect'],
    ]);

    app(ProspectImportService::class)->import($sessionSkip);
    $sessionSkip->refresh();

    expect($sessionSkip->success_rows)->toBe(0);
    expect($sessionSkip->error_rows)->toBe(2);
    expect(ImportSessionError::query()->where('session_id', $sessionSkip->id)->count())->toBe(2);

    $sessionUpdate = ImportSession::factory()->create([
        'user_id' => $user->id,
        'filename' => 'imports/prospects.csv',
        'original_filename' => 'prospects.csv',
        'status' => 'mapping',
        'column_mapping' => ['Nom' => 'name', 'Email' => 'email', 'Téléphone' => 'phone'],
        'options' => ['duplicate_strategy' => 'update', 'default_status' => 'prospect'],
    ]);

    app(ProspectImportService::class)->import($sessionUpdate);

    $existing->refresh();
    expect($existing->name)->toBe('New Name');
});
