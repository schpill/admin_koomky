<?php

use App\Enums\DocumentType;
use App\Services\DocumentTypeDetectorService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->detector = new DocumentTypeDetectorService;
});

test('it detects PDF', function () {
    $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
    $result = $this->detector->detect($file);

    expect($result->document_type)->toBe(DocumentType::PDF)
        ->and($result->script_language)->toBeNull()
        ->and($result->mime_type)->toBe('application/pdf');
});

test('it detects spreadsheet', function () {
    $file = UploadedFile::fake()->create('test.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $result = $this->detector->detect($file);

    expect($result->document_type)->toBe(DocumentType::SPREADSHEET);
});

test('it detects script via extension', function () {
    $file = UploadedFile::fake()->create('script.py', 100, 'text/x-python');
    $result = $this->detector->detect($file);

    expect($result->document_type)->toBe(DocumentType::SCRIPT)
        ->and($result->script_language)->toBe('python');
});

test('it detects javascript script', function () {
    $file = UploadedFile::fake()->create('app.js', 100, 'application/javascript');
    $result = $this->detector->detect($file);

    expect($result->document_type)->toBe(DocumentType::SCRIPT)
        ->and($result->script_language)->toBe('javascript');
});

test('it detects image', function () {
    $file = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg');
    $result = $this->detector->detect($file);

    expect($result->document_type)->toBe(DocumentType::IMAGE);
});

test('it rejects dangerous mimes', function () {
    $file = UploadedFile::fake()->create('malicious.exe', 100, 'application/x-msdownload');

    expect(fn () => $this->detector->detect($file))->toThrow(\InvalidArgumentException::class);
});

test('it maps unknown mime to other', function () {
    $file = UploadedFile::fake()->create('unknown.xyz', 100, 'application/octet-stream');
    $result = $this->detector->detect($file);

    expect($result->document_type)->toBe(DocumentType::OTHER);
});
