<?php

namespace App\Jobs;

use App\Models\ImportSession;
use App\Services\ProspectImportService;
use App\Services\WebhookDispatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessProspectImportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $sessionId) {}

    public function handle(ProspectImportService $prospectImportService, WebhookDispatchService $webhookDispatchService): void
    {
        $session = ImportSession::query()->find($this->sessionId);
        if (! $session) {
            return;
        }

        try {
            $prospectImportService->import($session);
        } catch (Throwable $exception) {
            $session->update([
                'status' => 'failed',
                'error_summary' => $exception->getMessage(),
                'completed_at' => now(),
            ]);
        }

        $session->refresh();

        $webhookDispatchService->dispatch('client.imported', [
            'session_id' => $session->id,
            'success_rows' => (int) $session->success_rows,
            'error_rows' => (int) $session->error_rows,
            'tags_applied' => is_array($session->default_tags) ? $session->default_tags : [],
        ], $session->user_id);
    }
}
