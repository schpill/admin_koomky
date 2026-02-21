<?php

use App\Models\Document;
use App\Models\User;
use App\Services\DocumentStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->service = new DocumentStorageService;
    $this->user = User::factory()->create(['document_storage_quota_mb' => 1]); // 1MB
});

test('it stores a file correctly', function () {
    $file = UploadedFile::fake()->createWithContent('test.pdf', 'dummy content');
    $path = $this->service->store($file, $this->user);

    expect($path)->toContain($this->user->id)
        ->and(Storage::disk('local')->exists($path))->toBeTrue();
});

test('it throws exception if quota exceeded', function () {
    $this->user->update(['document_storage_quota_mb' => 0]); // 0MB quota
    $file = UploadedFile::fake()->create('large.pdf', 100);

    expect(fn () => $this->service->store($file, $this->user))
        ->toThrow(\RuntimeException::class, 'Storage quota exceeded');
});

test('it overwrites a file', function () {
    $file = UploadedFile::fake()->createWithContent('old.pdf', 'old content');
    $path = $this->service->store($file, $this->user);

    $newFile = UploadedFile::fake()->createWithContent('new.pdf', 'new longer content');
    $this->service->overwrite($path, $newFile, $this->user);

    expect(Storage::disk('local')->exists($path))->toBeTrue()
        ->and(Storage::disk('local')->get($path))->toBe('new longer content');
});

test('it deletes a file', function () {
    $file = UploadedFile::fake()->create('delete-me.pdf', 100);
    $path = $this->service->store($file, $this->user);

    $this->service->delete($path);

    expect(Storage::disk('local')->exists($path))->toBeFalse();
});

test('it calculates total used bytes', function () {
    Document::factory()->create(['user_id' => $this->user->id, 'file_size' => 1000]);
    Document::factory()->create(['user_id' => $this->user->id, 'file_size' => 2500]);

    expect($this->service->getTotalUsedBytes($this->user))->toBe(3500);
});
