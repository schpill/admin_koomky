<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Campaigns\StoreCampaignRequest;
use App\Http\Requests\Api\V1\Campaigns\StoreCampaignTestRequest;
use App\Jobs\SendEmailCampaignJob;
use App\Jobs\SendSmsCampaignJob;
use App\Mail\CampaignTestMail;
use App\Models\Campaign;
use App\Models\CampaignAttachment;
use App\Models\CampaignVariant;
use App\Models\User;
use App\Services\MailConfigService;
use App\Services\PersonalizationService;
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
        private readonly SmsProviderManager $smsProviderManager,
        private readonly PersonalizationService $personalizationService,
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
            $isAbTest = (bool) ($validated['is_ab_test'] ?? false);
            $resolvedSubject = (string) ($validated['subject'] ?? ($isAbTest ? $this->fallbackVariantSubject($validated) : ''));
            $resolvedContent = (string) ($validated['content'] ?? ($isAbTest ? $this->fallbackVariantContent($validated) : ''));

            /** @var Campaign $campaign */
            $campaign = Campaign::query()->create([
                'user_id' => $user->id,
                'segment_id' => $validated['segment_id'] ?? null,
                'template_id' => $validated['template_id'] ?? null,
                'name' => $validated['name'],
                'type' => $validated['type'],
                'status' => $validated['status'] ?? 'draft',
                'subject' => $resolvedSubject !== '' ? $resolvedSubject : null,
                'content' => $resolvedContent,
                'scheduled_at' => $validated['scheduled_at'] ?? null,
                'settings' => $validated['settings'] ?? null,
                'is_ab_test' => $isAbTest,
                'ab_winner_criteria' => $validated['ab_winner_criteria'] ?? null,
                'ab_auto_select_after_hours' => $validated['ab_auto_select_after_hours'] ?? null,
            ]);

            $this->syncAttachments($campaign, $attachments);
            $this->syncVariants($campaign, $validated);

            return $campaign;
        });

        return $this->success($campaign->load(['segment', 'template', 'attachments', 'variants', 'winnerVariant']), 'Campaign created successfully', 201);
    }

    public function show(Campaign $campaign): JsonResponse
    {
        Gate::authorize('view', $campaign);

        return $this->success($campaign->load(['segment', 'template', 'attachments', 'recipients.contact', 'variants', 'winnerVariant']), 'Campaign retrieved successfully');
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

            $updatePayload = $validated;
            $isAbTest = (bool) ($validated['is_ab_test'] ?? $campaign->is_ab_test);

            if ($isAbTest) {
                $updatePayload['subject'] = $validated['subject'] ?? $this->fallbackVariantSubject($validated) ?? $campaign->subject;
                $updatePayload['content'] = $validated['content'] ?? $this->fallbackVariantContent($validated) ?? $campaign->content;
            }

            $campaign->update($updatePayload);

            if (array_key_exists('attachments', $validated)) {
                $this->syncAttachments($campaign, $attachments);
            }
            $this->syncVariants($campaign, $validated);

            return $campaign;
        });

        return $this->success($campaign->load(['segment', 'template', 'attachments', 'variants', 'winnerVariant']), 'Campaign updated successfully');
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

    public function testSend(StoreCampaignTestRequest $request, Campaign $campaign): JsonResponse
    {
        Gate::authorize('view', $campaign);

        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        if ($campaign->type === 'sms') {
            $phones = array_values(array_filter($validated['phones'] ?? [], fn (mixed $phone): bool => is_string($phone) && $phone !== ''));

            foreach ($phones as $phone) {
                $this->smsProviderManager->send(
                    (array) ($user->sms_settings ?? []),
                    (string) $phone,
                    (string) $campaign->content
                );
            }
        } else {
            $emails = array_values(array_filter($validated['emails'] ?? [], fn (mixed $email): bool => is_string($email) && $email !== ''));
            $renderedSubject = $this->personalizationService->renderPreview((string) ($campaign->subject ?? 'Campaign test'));
            $renderedBody = $this->personalizationService->renderPreview((string) $campaign->content);

            $mailer = $this->mailConfigService->configureForUser($user);

            foreach ($emails as $email) {
                Mail::mailer($mailer)
                    ->to((string) $email)
                    ->send(new CampaignTestMail($renderedSubject, $renderedBody));
            }
        }

        return $this->success(null, 'Test campaign sent successfully');
    }

    public function selectWinner(Request $request, Campaign $campaign): JsonResponse
    {
        Gate::authorize('update', $campaign);

        if (! $campaign->isAbTest()) {
            return $this->error('Campaign is not configured as A/B test', 422);
        }

        $validated = $request->validate([
            'variant_id' => [
                'required',
                'uuid',
                \Illuminate\Validation\Rule::exists('campaign_variants', 'id')
                    ->where(fn ($query) => $query->where('campaign_id', $campaign->id)),
            ],
        ]);

        $campaign->update([
            'ab_winner_variant_id' => $validated['variant_id'],
            'ab_winner_selected_at' => now(),
            'ab_winner_criteria' => 'manual',
        ]);

        $campaign->load(['variants', 'winnerVariant']);

        return $this->success($campaign, 'A/B winner selected successfully');
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

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncVariants(Campaign $campaign, array $validated): void
    {
        if (! $campaign->isAbTest()) {
            $campaign->variants()->delete();

            return;
        }

        $rawVariants = $validated['variants'] ?? [];
        if (! is_array($rawVariants) || $rawVariants === []) {
            return;
        }

        foreach ($rawVariants as $rawVariant) {
            if (! is_array($rawVariant)) {
                continue;
            }

            CampaignVariant::query()->updateOrCreate(
                [
                    'campaign_id' => $campaign->id,
                    'label' => (string) ($rawVariant['label'] ?? ''),
                ],
                [
                    'subject' => $rawVariant['subject'] ?? null,
                    'content' => $rawVariant['content'] ?? null,
                    'send_percent' => (int) ($rawVariant['send_percent'] ?? 50),
                ]
            );
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function fallbackVariantSubject(array $validated): ?string
    {
        $variants = $validated['variants'] ?? [];
        if (! is_array($variants)) {
            return null;
        }

        foreach ($variants as $variant) {
            if (! is_array($variant)) {
                continue;
            }

            if ((string) ($variant['label'] ?? '') === 'A') {
                return isset($variant['subject']) && is_string($variant['subject']) ? $variant['subject'] : null;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function fallbackVariantContent(array $validated): ?string
    {
        $variants = $validated['variants'] ?? [];
        if (! is_array($variants)) {
            return null;
        }

        foreach ($variants as $variant) {
            if (! is_array($variant)) {
                continue;
            }

            if ((string) ($variant['label'] ?? '') === 'A') {
                return isset($variant['content']) && is_string($variant['content']) ? $variant['content'] : null;
            }
        }

        return null;
    }
}
