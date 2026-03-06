<?php

use App\Models\ImportSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

test('prune command deletes old completed and failed sessions but keeps recent and processing ones', function () {
    $oldCompleted = ImportSession::factory()->create(['status' => 'completed', 'filename' => 'imports/old-completed.csv']);
    $oldCompleted->forceFill(['updated_at' => now()->subDays(40)])->save();

    $oldFailed = ImportSession::factory()->create(['status' => 'failed', 'filename' => 'imports/old-failed.csv']);
    $oldFailed->forceFill(['updated_at' => now()->subDays(35)])->save();

    $recentCompleted = ImportSession::factory()->create(['status' => 'completed', 'filename' => 'imports/recent.csv']);
    $processing = ImportSession::factory()->create(['status' => 'processing', 'filename' => 'imports/processing.csv']);

    Storage::disk('local')->put($oldCompleted->filename, 'x');
    Storage::disk('local')->put($oldFailed->filename, 'x');
    Storage::disk('local')->put($recentCompleted->filename, 'x');
    Storage::disk('local')->put($processing->filename, 'x');

    $this->artisan('import-sessions:prune')->assertExitCode(0);

    expect(ImportSession::query()->whereKey($oldCompleted->id)->exists())->toBeFalse();
    expect(ImportSession::query()->whereKey($oldFailed->id)->exists())->toBeFalse();
    expect(ImportSession::query()->whereKey($recentCompleted->id)->exists())->toBeTrue();
    expect(ImportSession::query()->whereKey($processing->id)->exists())->toBeTrue();
});
