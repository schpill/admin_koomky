<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignTemplate;
use App\Models\Client;
use App\Models\Contact;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Segment;
use App\Models\Tag;
use App\Models\User;
use RuntimeException;
use ZipArchive;

class DataExportService
{
    /**
     * @return array<string, mixed>
     */
    public function buildPayload(User $user): array
    {
        $clients = Client::query()
            ->where('user_id', $user->id)
            ->with(['contacts', 'tags'])
            ->get();

        $projects = Project::query()
            ->where('user_id', $user->id)
            ->with(['tasks', 'timeEntries'])
            ->get();

        $invoices = Invoice::query()
            ->where('user_id', $user->id)
            ->with(['lineItems', 'payments'])
            ->get();

        $quotes = Quote::query()
            ->where('user_id', $user->id)
            ->with(['lineItems'])
            ->get();

        $creditNotes = CreditNote::query()
            ->where('user_id', $user->id)
            ->with(['lineItems'])
            ->get();

        $campaigns = Campaign::query()
            ->where('user_id', $user->id)
            ->with(['recipients'])
            ->get();

        return [
            'exported_at' => now()->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'settings' => [
                'business_name' => $user->business_name,
                'payment_terms_days' => $user->payment_terms_days,
                'invoice_numbering_pattern' => $user->invoice_numbering_pattern,
                'email_settings' => $user->email_settings,
                'sms_settings' => $user->sms_settings,
                'notification_preferences' => $user->notification_preferences,
            ],
            'clients' => $clients->toArray(),
            'contacts' => Contact::query()
                ->whereIn('client_id', $clients->pluck('id'))
                ->get()
                ->toArray(),
            'tags' => Tag::query()
                ->where('user_id', $user->id)
                ->with('clients')
                ->get()
                ->toArray(),
            'projects' => $projects->toArray(),
            'invoices' => $invoices->toArray(),
            'quotes' => $quotes->toArray(),
            'credit_notes' => $creditNotes->toArray(),
            'campaigns' => $campaigns->toArray(),
            'campaign_templates' => CampaignTemplate::query()
                ->where('user_id', $user->id)
                ->get()
                ->toArray(),
            'segments' => Segment::query()
                ->where('user_id', $user->id)
                ->get()
                ->toArray(),
        ];
    }

    public function createArchive(User $user): string
    {
        $payload = $this->buildPayload($user);

        $archivePath = tempnam(sys_get_temp_dir(), 'koomky-export-');
        if ($archivePath === false) {
            throw new RuntimeException('Unable to create temporary archive file');
        }

        $zip = new ZipArchive;
        $opened = $zip->open($archivePath, ZipArchive::OVERWRITE);
        if ($opened !== true) {
            throw new RuntimeException('Unable to open ZIP archive for export');
        }

        $json = json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        $zip->addFromString('export.json', $json);
        $zip->close();

        return $archivePath;
    }
}
