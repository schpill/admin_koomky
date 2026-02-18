<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Quotes\QuoteResource;
use App\Models\Client;
use App\Models\PortalAccessToken;
use App\Models\PortalSettings;
use App\Models\Quote;
use App\Notifications\QuoteAcceptedNotification;
use App\Services\PortalActivityLogger;
use App\Services\QuotePdfService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PortalQuoteController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PortalActivityLogger $portalActivityLogger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $client = $this->resolveClient($request);
        if (! $client) {
            return $this->error('Portal session is invalid', 401);
        }

        $query = Quote::query()
            ->where('client_id', $client->id)
            ->whereIn('status', ['sent', 'accepted', 'rejected', 'expired'])
            ->with(['client', 'project'])
            ->orderByDesc('issue_date');

        $quotes = $query->paginate((int) $request->input('per_page', 15));

        /** @var array<string, mixed> $collectionPayload */
        $collectionPayload = QuoteResource::collection($quotes)->response()->getData(true);

        return $this->success([
            'data' => $collectionPayload['data'] ?? [],
            'current_page' => $quotes->currentPage(),
            'per_page' => $quotes->perPage(),
            'total' => $quotes->total(),
            'last_page' => $quotes->lastPage(),
        ], 'Portal quotes retrieved successfully');
    }

    public function show(Request $request, Quote $quote): JsonResponse
    {
        $client = $this->resolveClient($request);
        if (! $client || ! $this->canAccessQuote($client, $quote)) {
            return $this->error('Quote not found', 404);
        }

        $quote->load(['client', 'project', 'lineItems']);

        $this->portalActivityLogger->log(
            $request,
            $client,
            'view_quote',
            $this->resolvePortalAccessToken($request),
            Quote::class,
            $quote->id
        );

        return $this->success(new QuoteResource($quote), 'Portal quote retrieved successfully');
    }

    public function pdf(Request $request, Quote $quote, QuotePdfService $pdfService): Response|JsonResponse
    {
        $client = $this->resolveClient($request);
        if (! $client || ! $this->canAccessQuote($client, $quote)) {
            return $this->error('Quote not found', 404);
        }

        $this->portalActivityLogger->log(
            $request,
            $client,
            'download_pdf',
            $this->resolvePortalAccessToken($request),
            Quote::class,
            $quote->id
        );

        $pdf = $pdfService->generate($quote);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$quote->number.'.pdf"',
        ]);
    }

    public function accept(Request $request, Quote $quote): JsonResponse
    {
        $client = $this->resolveClient($request);
        if (! $client || ! $this->canAccessQuote($client, $quote)) {
            return $this->error('Quote not found', 404);
        }

        if ($quote->status === 'accepted') {
            return $this->error('Quote is already accepted', 422);
        }

        if (! $quote->canTransitionTo('accepted')) {
            return $this->error('Invalid quote status transition', 422);
        }

        $settings = $request->attributes->get('portal_settings');
        if ($settings instanceof PortalSettings && ! $settings->quote_acceptance_enabled) {
            return $this->error('Quote acceptance is disabled', 403);
        }

        $quote->forceFill([
            'status' => 'accepted',
            'accepted_at' => now(),
        ])->save();

        if ($quote->user) {
            $quote->user->notify(new QuoteAcceptedNotification($quote, 'accepted'));
        }

        $this->portalActivityLogger->log(
            $request,
            $client,
            'accept_quote',
            $this->resolvePortalAccessToken($request),
            Quote::class,
            $quote->id
        );

        $quote->refresh()->load(['client', 'project', 'lineItems']);

        return $this->success(new QuoteResource($quote), 'Quote accepted successfully');
    }

    public function reject(Request $request, Quote $quote): JsonResponse
    {
        $client = $this->resolveClient($request);
        if (! $client || ! $this->canAccessQuote($client, $quote)) {
            return $this->error('Quote not found', 404);
        }

        if (! $quote->canTransitionTo('rejected')) {
            return $this->error('Invalid quote status transition', 422);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $quote->forceFill([
            'status' => 'rejected',
        ])->save();

        $reason = is_string($validated['reason'] ?? null) ? $validated['reason'] : null;

        if ($quote->user) {
            $quote->user->notify(new QuoteAcceptedNotification($quote, 'rejected', $reason));
        }

        $this->portalActivityLogger->log(
            $request,
            $client,
            'reject_quote',
            $this->resolvePortalAccessToken($request),
            Quote::class,
            $quote->id
        );

        $quote->refresh()->load(['client', 'project', 'lineItems']);

        return $this->success(new QuoteResource($quote), 'Quote rejected successfully');
    }

    private function resolveClient(Request $request): ?Client
    {
        $client = $request->attributes->get('portal_client');

        return $client instanceof Client ? $client : null;
    }

    private function resolvePortalAccessToken(Request $request): ?PortalAccessToken
    {
        $portalAccessToken = $request->attributes->get('portal_access_token');

        return $portalAccessToken instanceof PortalAccessToken ? $portalAccessToken : null;
    }

    private function canAccessQuote(Client $client, Quote $quote): bool
    {
        return $quote->client_id === $client->id
            && in_array($quote->status, ['sent', 'accepted', 'rejected', 'expired'], true);
    }
}
