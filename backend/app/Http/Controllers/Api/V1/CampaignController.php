<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Campaigns\StoreCampaignRequest;
use App\Jobs\SendEmailCampaignJob;
use App\Jobs\SendSmsCampaignJob;
use App\Mail\CampaignTestMail;
use App\Models\Campaign;
use App\Models\CampaignAttachment;
use App\Models\User;
use App\Services\MailConfigService;
use App\Services\Sms\SmsProviderManager;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

class CampaignController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MailConfigService $mailConfigService,
        private readonly SmsProviderManager $smsProviderManager
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Campaign::query()
            ->where('user_id', $user->id)
            ->with(['segment', 'template'])
            ->withCount('recipients');

        if ($request->filled('type') && is_string($request->input('type'))) {
            $query->byType($request->input('type'));
        }

        if ($request->filled('status') && is_string($request->input('status'))) {
            $query->byStatus($request->input('status'));
        }

        $campaigns = $query->latest()->paginate((int) $request->input('per_page', 15));

        return $this->success([
            'data' => $campaigns->items(),
            'current_page' => $campaigns->currentPage(),
            'per_page' => $campaigns->perPage(),
            'total' => $campaigns->total(),
            'last_page' => $campaigns->lastPage(),
        ], 'Campaigns retrieved successfully');
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        Gate::authorize('create', Campaign::class);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        $campaign = DB::transaction(function () use ($validated, $user): Campaign {
            $attachments = $this->extractAttachments($validated);
            $attachmentTotalSize = $this->attachmentTotalSize($attachments);
            if ($attachmentTotalSize > 5 * 1024 * 1024) {
                abort(422, 'Total attachment size cannot exceed 5MB');
            }

            /** @var Campaign $campaign */
            $campaign = Campaign::query()->create([
                'user_id' => $user->id,
                'segment_id' => $validated['segment_id'] ?? null,
                'template_id' => $validated['template_id'] ?? null,
                'name' => $validated['name'],
                'type' => $validated['type'],
                'status' => $validated['status'] ?? 'draft',
                'subject' => $validated['subject'] ?? null,
                'content' => $validated['content'],
                'scheduled_at' => $validated['scheduled_at'] ?? null,
                'settings' => $validated['settings'] ?? null,
            ]);

            $this->syncAttachments($campaign, $attachments);

            return $campaign;
        });

        return $this->success($campaign->load(['segment', 'template', 'attachments']), 'Campaign created successfully', 201);
    }

    public function show(Campaign $campaign): JsonResponse
    {
        Gate::authorize('view', $campaign);

        return $this->success($campaign->load(['segment', 'template', 'attachments', 'recipients.contact']), 'Campaign retrieved successfully');
    }

    public function update(StoreCampaignRequest $request, Campaign $campaign): JsonResponse
    {
        Gate::authorize('update', $campaign);

        $validated = $request->validated();

        $campaign = DB::transaction(function () use ($campaign, $validated): Campaign {
            $attachments = $this->extractAttachments($validated);
            $attachmentTotalSize = $this->attachmentTotalSize($attachments);
            if ($attachmentTotalSize > 5 * 1024 * 1024) {
                abort(422, 'Total attachment size cannot exceed 5MB');
            }

            $campaign->update($validated);

            if (array_key_exists('attachments', $validated)) {
                $this->syncAttachments($campaign, $attachments);
            }

            return $campaign;
        });

        return $this->success($campaign->load(['segment', 'template', 'attachments']), 'Campaign updated successfully');
    }

    public function destroy(Campaign $campaign): JsonResponse
    {
        Gate::authorize('delete', $campaign);

        $campaign->delete();

        return $this->success(null, 'Campaign deleted successfully');
    }

    public function send(Campaign $campaign): JsonResponse
    {
        Gate::authorize('update', $campaign);

        if (! $campaign->canTransitionTo('sending')) {
            return $this->error('Invalid campaign status transition', 422);
        }

        $campaign->update([
            'status' => 'sending',
            'started_at' => now(),
        ]);

        if ($campaign->type === 'sms') {
            SendSmsCampaignJob::dispatch($campaign->id);
        } else {
            SendEmailCampaignJob::dispatch($campaign->id);
        }

        return $this->success($campaign->fresh(), 'Campaign send queued');
    }

    public function pause(Campaign $campaign): JsonResponse
    {
        Gate::authorize('update', $campaign);

        if (! $campaign->canTransitionTo('paused')) {
            return $this->error('Campaign cannot be paused from current state', 422);
        }

        $campaign->update(['status' => 'paused']);

        return $this->success($campaign, 'Campaign paused');
    }

    public function duplicate(Campaign $campaign): JsonResponse
    {
        Gate::authorize('view', $campaign);

        /** @var Campaign $clone */
        $clone = DB::transaction(function () use ($campaign): Campaign {
            $campaign->load('attachments');

            $clone = Campaign::query()->create([
                'user_id' => $campaign->user_id,
                'segment_id' => $campaign->segment_id,
                'template_id' => $campaign->template_id,
                'name' => $campaign->name.' Copy',
                'type' => $campaign->type,
                'status' => 'draft',
                'subject' => $campaign->subject,
                'content' => $campaign->content,
                'scheduled_at' => null,
                'started_at' => null,
                'completed_at' => null,
                'settings' => $campaign->settings,
            ]);

            foreach ($campaign->attachments as $attachment) {
                $clone->attachments()->create([
                    'filename' => $attachment->filename,
                    'path' => $attachment->path,
                    'mime_type' => $attachment->mime_type,
                    'size_bytes' => $attachment->size_bytes,
                ]);
            }

            return $clone;
        });

        return $this->success($clone->load(['segment', 'template', 'attachments']), 'Campaign duplicated successfully', 201);
    }

    public function testSend(Request $request, Campaign $campaign): JsonResponse
    {
        Gate::authorize('view', $campaign);

        /** @var User $user */
        $user = $request->user();

        if ($campaign->type === 'sms') {
            $validated = $request->validate([
                'phone' => ['required', 'string', 'max:20'],
            ]);

            $this->smsProviderManager->send(
                (array) ($user->sms_settings ?? []),
                (string) $validated['phone'],
                (string) $campaign->content
            );
        } else {
            $validated = $request->validate([
                'email' => ['required', 'email', 'max:255'],
            ]);

            $mailer = $this->mailConfigService->configureForUser($user);

            Mail::mailer($mailer)
                ->to((string) $validated['email'])
                ->send(new CampaignTestMail($campaign));
        }

        return $this->success(null, 'Test campaign sent successfully');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, array<string, mixed>>
     */
    private function extractAttachments(array $validated): array
    {
        $rawAttachments = $validated['attachments'] ?? [];
        if (! is_array($rawAttachments)) {
            return [];
        }

        $attachments = [];

        foreach ($rawAttachments as $attachment) {
            if (is_array($attachment)) {
                $attachments[] = $attachment;
            }
        }

        return $attachments;
    }

    /**
     * @param  array<int, array<string, mixed>>  $attachments
     */
    private function attachmentTotalSize(array $attachments): int
    {
        $size = 0;

        foreach ($attachments as $attachment) {
            $size += (int) ($attachment['size_bytes'] ?? 0);
        }

        return $size;
    }

    /**
     * @param  array<int, array<string, mixed>>  $attachments
     */
    private function syncAttachments(Campaign $campaign, array $attachments): void
    {
        $campaign->attachments()->delete();

        foreach ($attachments as $attachment) {
            CampaignAttachment::query()->create([
                'campaign_id' => $campaign->id,
                'filename' => (string) ($attachment['filename'] ?? ''),
                'path' => (string) ($attachment['path'] ?? ''),
                'mime_type' => (string) ($attachment['mime_type'] ?? 'application/octet-stream'),
                'size_bytes' => (int) ($attachment['size_bytes'] ?? 0),
            ]);
        }
    }
}
