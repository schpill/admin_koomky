<?php

use App\Services\DocumentMailService;
use App\Models\Document;
use App\Models\User;
use App\Mail\DocumentAttachmentMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    $this->service = new DocumentMailService();
});

test('it sends an email and updates document metadata', function () {
    $document = Document::factory()->create([
        'title' => 'Invoice 123',
        'last_sent_at' => null,
        'last_sent_to' => null,
    ]);
    
    $recipient = 'client@example.com';
    $message = 'Here is your invoice.';

    $this->service->send($document, $recipient, $message);

    Mail::assertQueued(DocumentAttachmentMail::class, function ($mail) use ($document, $recipient) {
        return $mail->hasTo($recipient) && $mail->document->id === $document->id;
    });

    $document->refresh();
    expect($document->last_sent_at)->not->toBeNull()
        ->and($document->last_sent_to)->toBe($recipient);
});

test('it throws exception for invalid email', function () {
    $document = Document::factory()->create();
    
    expect(fn() => $this->service->send($document, 'invalid-email', 'msg'))
        ->toThrow(\InvalidArgumentException::class, 'Invalid recipient email address');

    Mail::assertNothingSent();
});
