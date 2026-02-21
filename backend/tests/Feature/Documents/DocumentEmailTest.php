<?php

use App\Models\User;
use App\Models\Document;
use App\Mail\DocumentAttachmentMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    Storage::fake('local');
    $this->user = User::factory()->create();
});

test('it sends document via email through API', function () {
    $document = Document::factory()->create(['user_id' => $this->user->id]);

    $response = $this->actingAs($this->user)->postJson("/api/v1/documents/{$document->id}/email", [
        'email' => 'client@example.com',
        'message' => 'Please find attached the file.',
    ]);

    $response->assertStatus(200);

    Mail::assertQueued(DocumentAttachmentMail::class, function ($mail) use ($document) {
        return $mail->hasTo('client@example.com') && $mail->document->id === $document->id;
    });

    $document->refresh();
    expect($document->last_sent_at)->not->toBeNull()
        ->and($document->last_sent_to)->toBe('client@example.com');
});
