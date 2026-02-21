<?php

namespace App\Observers;

use App\Models\Document;
use App\Services\ActivityService;
use App\Services\WebhookDispatchService;

class DocumentObserver
{
    /**
     * Handle the Document "created" event.
     */
    public function created(Document $document): void
    {
        $client = $document->client;
        if ($client) {
            ActivityService::log($client, "Document uploaded: {$document->title}", [
                'document_id' => $document->id,
                'document_type' => $document->document_type->value,
            ]);
        }

        $this->dispatchWebhook($document, 'document.uploaded');
    }

    /**
     * Handle the Document "updated" event.
     */
    public function updated(Document $document): void
    {
        // Check if it was sent
        if ($document->wasChanged('last_sent_at') && $document->last_sent_at !== null) {
            $this->dispatchWebhook($document, 'document.sent', [
                'sent_to' => $document->last_sent_to,
                'sent_at' => $document->last_sent_at->toIso8601String(),
            ]);

            return;
        }

        $this->dispatchWebhook($document, 'document.updated');
    }

    /**
     * Handle the Document "deleted" event.
     */
    public function deleted(Document $document): void
    {
        $this->dispatchWebhook($document, 'document.deleted');
    }

    /**
     * Dispatch a webhook for the document event.
     *
     * @param  array<string, mixed>  $extraData
     */
    private function dispatchWebhook(Document $document, string $event, array $extraData = []): void
    {
        $data = array_merge([
            'id' => $document->id,
            'title' => $document->title,
            'document_type' => $document->document_type->value,
            'client_id' => $document->client_id,
            'original_filename' => $document->original_filename,
            'file_size' => $document->file_size,
            'version' => $document->version,
            'tags' => $document->tags,
        ], $extraData);

        app(WebhookDispatchService::class)->dispatch($event, $data, $document->user_id);
    }
}
