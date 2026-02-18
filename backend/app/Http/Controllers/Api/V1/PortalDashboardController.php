<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Invoices\InvoiceResource;
use App\Http\Resources\Api\V1\Quotes\QuoteResource;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentIntent;
use App\Models\PortalAccessToken;
use App\Models\PortalSettings;
use App\Models\Quote;
use App\Models\User;
use App\Services\PortalActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalDashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PortalActivityLogger $portalActivityLogger,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $client = $this->resolveClient($request);
        if (! $client) {
            return $this->error('Portal session is invalid', 401);
        }

        /** @var User|null $user */
        $user = $client->user;

        $recentInvoices = Invoice::query()
            ->where('client_id', $client->id)
            ->whereIn('status', ['sent', 'viewed', 'partially_paid', 'overdue', 'paid'])
            ->with('client')
            ->orderByDesc('issue_date')
            ->limit(5)
            ->get();

        $recentQuotes = Quote::query()
            ->where('client_id', $client->id)
            ->whereIn('status', ['sent', 'accepted', 'rejected', 'expired'])
            ->with('client')
            ->orderByDesc('issue_date')
            ->limit(5)
            ->get();

        $recentPayments = PaymentIntent::query()
            ->where('client_id', $client->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (PaymentIntent $paymentIntent): array => [
                'id' => $paymentIntent->id,
                'invoice_id' => $paymentIntent->invoice_id,
                'amount' => (float) $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
                'status' => $paymentIntent->status,
                'paid_at' => $paymentIntent->paid_at?->toIso8601String(),
                'created_at' => $paymentIntent->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        $outstandingInvoices = $recentInvoices->filter(
            fn (Invoice $invoice): bool => in_array($invoice->status, ['sent', 'viewed', 'partially_paid', 'overdue'], true)
        );

        $outstandingTotal = round((float) $outstandingInvoices->sum(
            fn (Invoice $invoice): float => (float) $invoice->balance_due
        ), 2);

        $settings = $request->attributes->get('portal_settings');
        $welcomeMessage = $settings instanceof PortalSettings
            ? (string) ($settings->welcome_message ?? '')
            : '';

        $this->portalActivityLogger->log(
            $request,
            $client,
            'view_dashboard',
            $this->resolvePortalAccessToken($request)
        );

        return $this->success([
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
            ],
            'freelancer' => [
                'id' => $user?->id,
                'name' => $user?->name,
                'business_name' => $user?->business_name,
            ],
            'welcome_message' => $welcomeMessage,
            'branding' => [
                'custom_logo' => $settings instanceof PortalSettings ? $settings->custom_logo : null,
                'custom_color' => $settings instanceof PortalSettings ? $settings->custom_color : null,
            ],
            'outstanding_invoices' => [
                'count' => $outstandingInvoices->count(),
                'total' => $outstandingTotal,
                'currency' => $recentInvoices->first()?->currency ?? 'EUR',
            ],
            'recent_invoices' => InvoiceResource::collection($recentInvoices)->resolve(),
            'recent_quotes' => QuoteResource::collection($recentQuotes)->resolve(),
            'recent_payments' => $recentPayments,
        ], 'Portal dashboard retrieved successfully');
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
}
