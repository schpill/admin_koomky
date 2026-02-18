<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Invoices\InvoiceResource;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PortalAccessToken;
use App\Services\InvoicePdfService;
use App\Services\PortalActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PortalInvoiceController extends Controller
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

        $query = Invoice::query()
            ->where('client_id', $client->id)
            ->whereIn('status', ['sent', 'viewed', 'partially_paid', 'overdue', 'paid'])
            ->with(['client', 'project'])
            ->orderByDesc('issue_date');

        $invoices = $query->paginate((int) $request->input('per_page', 15));

        $this->portalActivityLogger->log(
            $request,
            $client,
            'view_invoice',
            $this->resolvePortalAccessToken($request)
        );

        /** @var array<string, mixed> $collectionPayload */
        $collectionPayload = InvoiceResource::collection($invoices)->response()->getData(true);

        return $this->success([
            'data' => $collectionPayload['data'] ?? [],
            'current_page' => $invoices->currentPage(),
            'per_page' => $invoices->perPage(),
            'total' => $invoices->total(),
            'last_page' => $invoices->lastPage(),
        ], 'Portal invoices retrieved successfully');
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $client = $this->resolveClient($request);
        if (! $client || ! $this->canAccessInvoice($client, $invoice)) {
            return $this->error('Invoice not found', 404);
        }

        $invoice->load(['client', 'project', 'lineItems', 'payments']);

        $this->portalActivityLogger->log(
            $request,
            $client,
            'view_invoice',
            $this->resolvePortalAccessToken($request),
            Invoice::class,
            $invoice->id
        );

        return $this->success(new InvoiceResource($invoice), 'Portal invoice retrieved successfully');
    }

    public function pdf(Request $request, Invoice $invoice, InvoicePdfService $pdfService): Response|JsonResponse
    {
        $client = $this->resolveClient($request);
        if (! $client || ! $this->canAccessInvoice($client, $invoice)) {
            return $this->error('Invoice not found', 404);
        }

        $this->portalActivityLogger->log(
            $request,
            $client,
            'download_pdf',
            $this->resolvePortalAccessToken($request),
            Invoice::class,
            $invoice->id
        );

        $pdf = $pdfService->generate($invoice);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$invoice->number.'.pdf"',
        ]);
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

    private function canAccessInvoice(Client $client, Invoice $invoice): bool
    {
        return $invoice->client_id === $client->id
            && in_array($invoice->status, ['sent', 'viewed', 'partially_paid', 'overdue', 'paid'], true);
    }
}
