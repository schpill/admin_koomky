<?php

use App\Enums\DocumentType;
use App\Models\Client;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('it can create a document via factory', function () {
    $document = Document::factory()->create();

    expect($document)->toBeInstanceOf(Document::class)
        ->and($document->id)->not->toBeNull()
        ->and($document->document_type)->toBeInstanceOf(DocumentType::class);
});

test('it belongs to a user', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create(['user_id' => $user->id]);

    expect($document->user)->toBeInstanceOf(User::class)
        ->and($document->user->id)->toBe($user->id);
});

test('it can belong to a client', function () {
    $client = Client::factory()->create();
    $document = Document::factory()->create(['client_id' => $client->id]);

    expect($document->client)->toBeInstanceOf(Client::class)
        ->and($document->client->id)->toBe($client->id);
});

test('it can have no client', function () {
    $document = Document::factory()->create(['client_id' => null]);

    expect($document->client)->toBeNull();
});

test('scope byType filters correctly', function () {
    Document::factory()->create(['document_type' => DocumentType::PDF]);
    Document::factory()->create(['document_type' => DocumentType::IMAGE]);

    expect(Document::byType(DocumentType::PDF->value)->count())->toBe(1);
});

test('scope byClient filters correctly', function () {
    $client = Client::factory()->create();
    Document::factory()->create(['client_id' => $client->id]);
    Document::factory()->create(['client_id' => Client::factory()->create()->id]);

    expect(Document::byClient($client->id)->count())->toBe(1);
});

test('scope byTag filters correctly', function () {
    Document::factory()->create(['tags' => ['important', 'invoice']]);
    Document::factory()->create(['tags' => ['draft']]);

    expect(Document::byTag('important')->count())->toBe(1);
    expect(Document::byTag('invoice')->count())->toBe(1);
    expect(Document::byTag('draft')->count())->toBe(1);
    expect(Document::byTag('other')->count())->toBe(0);
});

test('it has searchable array for scout', function () {
    $document = Document::factory()->create([
        'title' => 'Test Document',
        'tags' => ['test', 'unit'],
    ]);

    $searchableArray = $document->toSearchableArray();

    expect($searchableArray)->toBeArray()
        ->and($searchableArray['title'])->toBe('Test Document')
        ->and($searchableArray['tags'])->toBe(['test', 'unit'])
        ->and($searchableArray['document_type'])->toBe($document->document_type->value);
});
