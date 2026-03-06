<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignTemplate;
use App\Models\Client;
use App\Models\Contact;
use App\Models\CreditNote;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\DripEnrollment;
use App\Models\DripSequence;
use App\Models\DripStep;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ImportSession;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\Project;
use App\Models\Quote;
use App\Models\RagUsageLog;
use App\Models\ReminderDelivery;
use App\Models\Segment;
use App\Models\SuppressedEmail;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use RuntimeException;
use ZipArchive;

class DataExportService
{
    /**
     * @return array<string, mixed>
     */
    public function exportUserData(User $user): array
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
            ->with(['recipients', 'variants'])
            ->get();

        $dripSequences = DripSequence::query()
            ->where('user_id', $user->id)
            ->with(['steps', 'enrollments'])
            ->get();

        $dripEnrollments = DripEnrollment::query()
            ->whereIn('sequence_id', $dripSequences->pluck('id'))
            ->get();

        $suppressedEmails = SuppressedEmail::query()
            ->where('user_id', $user->id)
            ->get();

        $expenseCategories = ExpenseCategory::query()
            ->where('user_id', $user->id)
            ->get();

        $expenses = Expense::query()
            ->where('user_id', $user->id)
            ->with(['category', 'project', 'client'])
            ->get();

        $leads = Lead::query()
            ->where('user_id', $user->id)
            ->with(['activities'])
            ->get();

        $documents = Document::query()
            ->where('user_id', $user->id)
            ->get();

        $tickets = Ticket::query()
            ->where('user_id', $user->id)
            ->get();

        $ticketMessages = TicketMessage::query()
            ->whereHas('ticket', fn ($query) => $query->where('user_id', $user->id))
            ->where('is_internal', false)
            ->get();

        $documentChunks = DocumentChunk::query()
            ->where('user_id', $user->id)
            ->get(['id', 'document_id', 'chunk_index', 'token_count', 'created_at']);

        $ragUsageLogs = RagUsageLog::query()
            ->where('user_id', $user->id)
            ->get();

        $products = Product::query()
            ->where('user_id', $user->id)
            ->withTrashed()
            ->get();

        $productSales = ProductSale::query()
            ->where('user_id', $user->id)
            ->with(['product', 'client'])
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, ReminderDelivery> $reminderDeliveries */
        $reminderDeliveries = ReminderDelivery::query()
            ->where('user_id', $user->id)
            ->with(['invoice', 'step'])
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, ImportSession> $importSessionModels */
        $importSessionModels = ImportSession::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $importSessions = [];
        foreach ($importSessionModels as $session) {
            $importSessions[] = [
                'id' => $session->id,
                'original_filename' => $session->original_filename,
                'status' => $session->status,
                'total_rows' => $session->total_rows,
                'processed_rows' => $session->processed_rows,
                'success_rows' => $session->success_rows,
                'error_rows' => $session->error_rows,
                'completed_at' => $session->completed_at?->toIso8601String(),
                'created_at' => $session->created_at?->toIso8601String(),
            ];
        }

        $reminderDeliveriesData = [];
        foreach ($reminderDeliveries as $delivery) {
            $reminderDeliveriesData[] = [
                'id' => $delivery->id,
                'invoice_id' => $delivery->invoice_id,
                'invoice_number' => $delivery->invoice?->number,
                'step_number' => $delivery->step?->step_number,
                'delay_days' => $delivery->step?->delay_days,
                'sent_at' => $delivery->sent_at,
                'status' => $delivery->status,
                'error_message' => $delivery->error_message,
            ];
        }

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
            'campaign_variants' => $campaigns
                ->flatMap(fn (Campaign $campaign) => $campaign->variants->map(fn ($variant) => [
                    'id' => $variant->id,
                    'campaign_id' => $campaign->id,
                    'label' => $variant->label,
                    'subject' => $variant->subject,
                    'content' => $variant->content,
                    'send_percent' => (int) $variant->send_percent,
                    'sent_count' => (int) $variant->sent_count,
                    'open_count' => (int) $variant->open_count,
                    'click_count' => (int) $variant->click_count,
                ]))
                ->values()
                ->all(),
            'campaign_templates' => CampaignTemplate::query()
                ->where('user_id', $user->id)
                ->get()
                ->toArray(),
            'drip_sequences' => $dripSequences->toArray(),
            'drip_steps' => DripStep::query()
                ->whereIn('sequence_id', $dripSequences->pluck('id'))
                ->get()
                ->toArray(),
            'drip_enrollments' => $dripEnrollments->toArray(),
            'suppressed_emails' => $suppressedEmails->toArray(),
            'segments' => Segment::query()
                ->where('user_id', $user->id)
                ->get()
                ->toArray(),
            'expense_categories' => $expenseCategories->toArray(),
            'expenses' => $expenses->toArray(),
            'leads' => $leads->toArray(),
            'lead_activities' => LeadActivity::query()
                ->whereIn('lead_id', $leads->pluck('id'))
                ->get()
                ->toArray(),
            'documents' => $documents->toArray(),
            'document_chunks' => $documentChunks->toArray(),
            'rag_usage_logs' => $ragUsageLogs->toArray(),
            'tickets' => $tickets->toArray(),
            'ticket_messages' => $ticketMessages->toArray(),
            'products' => $products->toArray(),
            'product_sales' => $productSales->map(fn ($sale) => [
                'id' => $sale->id,
                'product_name' => $sale->product?->name,
                'client_name' => $sale->client?->name,
                'quantity' => $sale->quantity,
                'total_price' => $sale->total_price,
                'currency' => $sale->currency_code,
                'status' => $sale->status->value,
                'sold_at' => $sale->sold_at?->toIso8601String(),
            ])->toArray(),
            'reminder_deliveries' => $reminderDeliveriesData,
            'import_sessions' => $importSessions,
        ];
    }

    public function createArchive(User $user): string
    {
        $payload = $this->exportUserData($user);

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
        $csvRows = ['invoice_number,step_number,delay_days,sent_at,status'];
        foreach (($payload['reminder_deliveries'] ?? []) as $delivery) {
            if (! is_array($delivery)) {
                continue;
            }

            $csvRows[] = implode(',', [
                $this->escapeCsv((string) ($delivery['invoice_number'] ?? '')),
                $this->escapeCsv((string) ($delivery['step_number'] ?? '')),
                $this->escapeCsv((string) ($delivery['delay_days'] ?? '')),
                $this->escapeCsv((string) ($delivery['sent_at'] ?? '')),
                $this->escapeCsv((string) ($delivery['status'] ?? '')),
            ]);
        }
        $zip->addFromString('reminder_deliveries.csv', implode("\n", $csvRows));
        $zip->close();

        return $archivePath;
    }

    private function escapeCsv(string $value): string
    {
        $escaped = str_replace('"', '""', $value);

        return '"'.$escaped.'"';
    }
}
