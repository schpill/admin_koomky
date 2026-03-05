<?php

use App\Models\ImportSession;
use App\Models\User;
use App\Services\DataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('gdpr export includes only current user import sessions', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    ImportSession::factory()->create(['user_id' => $user->id, 'original_filename' => 'mine.csv']);
    ImportSession::factory()->create(['user_id' => $other->id, 'original_filename' => 'other.csv']);

    $data = app(DataExportService::class)->exportUserData($user);

    expect($data)->toHaveKey('import_sessions');
    expect(count($data['import_sessions']))->toBe(1);
    expect($data['import_sessions'][0]['original_filename'])->toBe('mine.csv');
});
