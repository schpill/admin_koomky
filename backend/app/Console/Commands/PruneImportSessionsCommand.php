<?php

namespace App\Console\Commands;

use App\Models\ImportSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneImportSessionsCommand extends Command
{
    protected $signature = 'import-sessions:prune';

    protected $description = 'Delete completed/failed import sessions older than 30 days and remove their stored files';

    public function handle(): int
    {
        $sessions = ImportSession::query()
            ->whereIn('status', ['completed', 'failed'])
            ->where('updated_at', '<', now()->subDays(30))
            ->get();

        foreach ($sessions as $session) {
            Storage::disk('local')->delete($session->filename);
            $session->delete();
        }

        $this->info("Pruned {$sessions->count()} import sessions.");

        return self::SUCCESS;
    }
}
