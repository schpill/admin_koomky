<?php

namespace App\Services;

use App\Mail\DocumentAttachmentMail;
use App\Models\Document;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class DocumentMailService
{
    public function send(Document $document, string $recipientEmail, ?string $message = null): void
    {
        $validator = Validator::make(['email' => $recipientEmail], [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Invalid recipient email address');
        }

        Mail::to($recipientEmail)->send(new DocumentAttachmentMail($document, $message));

        $document->update([
            'last_sent_at' => now(),
            'last_sent_to' => $recipientEmail,
        ]);
    }
}
