<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreditNotes\StoreCreditNoteRequest;
use App\Http\Requests\Api\V1\CreditNotes\UpdateCreditNoteRequest;
use App\Http\Resources\Api\V1\CreditNotes\CreditNoteResource;
use App\Jobs\SendCreditNoteJob;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\User;
use App\Services\ApplyCreditNoteService;
use App\Services\CreditNotePdfService;
use App\Services\CurrencyConversionService;
use App\Services\InvoiceCalculationService;
use App\Services\ReferenceGenerator;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class CreditNoteController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = CreditNote::query()
            ->where('user_id', $user->id)
            ->with(['client', 'invoice']);

        if ($request->filled('status') && is_string($request->input('status'))) {
            $query->byStatus($request->input('status'));
        }

        if ($request->filled('client_id') && is_string($request->input('client_id'))) {
            $query->byClient($request->input('client_id'));
        }

        if ($request->filled('invoice_id') && is_string($request->input('invoice_id'))) {
            $query->byInvoice($request->input('invoice_id'));
        }

        if ($request->filled('date_from') && is_string($request->input('date_from'))) {
            $query->whereDate('issue_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to') && is_string($request->input('date_to'))) {
            $query->whereDate('issue_date', '<=', $request->input('date_to'));
        }

        $sortField = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortFields = ['number', 'status', 'issue_date', 'total', 'created_at'];
        if (is_string($sortField) && in_array($sortField, $allowedSortFields, true)) {
            $query->orderBy($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $creditNotes = $query->paginate((int) ($request->input('per_page', 15)));

        /** @var array<string, mixed> $collectionPayload */
        $collectionPayload = CreditNoteResource::collection($creditNotes)->response()->getData(true);

        return $this->success([
            'data' => $collectionPayload['data'] ?? [],
            'current_page' => $creditNotes->currentPage(),
            'per_page' => $creditNotes->perPage(),
            'total' => $creditNotes->total(),
            'last_page' => $creditNotes->lastPage(),
        ], 'Credit notes retrieved successfully');
    }

    public function store(
        StoreCreditNoteRequest $request,
        InvoiceCalculationService $calculationService,
        CurrencyConversionService $currencyConversionService
    ): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        /** @var Invoice $invoice */
        $invoice = Invoice::query()->where('user_id', $user->id)->findOrFail((string) $validated['invoice_id']);

        try {
            /** @var CreditNote $creditNote */
            $creditNote = DB::transaction(function () use ($validated, $user, $invoice, $calculationService, $currencyConversionService): CreditNote {
                /** @var array<int, array<string, mixed>> $lineItems */
                $lineItems = $validated['line_items'];
                $calculation = $calculationService->calculate($lineItems);
                $currency = strtoupper((string) ($validated['currency'] ?? $invoice->currency));
                $baseCurrency = strtoupper((string) ($user->base_currency ?? 'EUR'));
                $issueDate = Carbon::parse((string) $validated['issue_date']);

                if ((float) $calculation['total'] > (float) $invoice->balance_due) {
                    throw new RuntimeException('Credit note total exceeds invoice remaining balance');
                }

                $exchangeRate = $currencyConversionService->rateFor(
                    $currency,
                    $baseCurrency,
                    $issueDate
                );
                $baseCurrencyTotal = $currencyConversionService->convert(
                    (float) $calculation['total'],
                    $currency,
                    $baseCurrency,
                    $issueDate
                );

                $creditNote = CreditNote::query()->create([
                    'user_id' => $user->id,
                    'client_id' => $invoice->client_id,
                    'invoice_id' => $invoice->id,
                    'number' => ReferenceGenerator::generate('credit_notes', 'AVO'),
                    'status' => 'draft',
                    'issue_date' => $issueDate->toDateString(),
                    'subtotal' => $calculation['subtotal'],
                    'tax_amount' => $calculation['tax_amount'],
                    'total' => $calculation['total'],
                    'currency' => $currency,
                    'base_currency' => $baseCurrency,
                    'exchange_rate' => $exchangeRate,
                    'base_currency_total' => $baseCurrencyTotal,
                    'reason' => $validated['reason'] ?? null,
                ]);

                foreach ($lineItems as $index => $lineItem) {
                    $creditNote->lineItems()->create([
                        'description' => $lineItem['description'],
                        'quantity' => $lineItem['quantity'],
                        'unit_price' => $lineItem['unit_price'],
                        'vat_rate' => $lineItem['vat_rate'],
                        'sort_order' => $index,
                    ]);
                }

                return $creditNote;
            });
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $creditNote->load(['client', 'invoice', 'lineItems']);

        return $this->success(new CreditNoteResource($creditNote), 'Credit note created successfully', 201);
    }

    public function show(CreditNote $creditNote): JsonResponse
    {
        Gate::authorize('view', $creditNote);

        $creditNote->load(['client', 'invoice', 'lineItems']);

        return $this->success(new CreditNoteResource($creditNote), 'Credit note retrieved successfully');
    }

    public function update(
        UpdateCreditNoteRequest $request,
        CreditNote $creditNote,
        InvoiceCalculationService $calculationService,
        CurrencyConversionService $currencyConversionService
    ): JsonResponse
    {
        Gate::authorize('update', $creditNote);

        if ($creditNote->status !== 'draft') {
            return $this->error('Only draft credit notes can be updated', 422);
        }

        $validated = $request->validated();

        try {
            DB::transaction(function () use ($creditNote, $validated, $calculationService, $currencyConversionService): void {
                /** @var array<int, array<string, mixed>> $lineItems */
                $lineItems = $validated['line_items'];
                $calculation = $calculationService->calculate($lineItems);

                $invoice = $creditNote->invoice;
                if (! ($invoice instanceof Invoice)) {
                    throw new RuntimeException('Credit note invoice not found');
                }

                if ((float) $calculation['total'] > (float) $invoice->balance_due) {
                    throw new RuntimeException('Credit note total exceeds invoice remaining balance');
                }

                $currency = strtoupper((string) ($validated['currency'] ?? $creditNote->currency));
                $baseCurrency = strtoupper((string) ($creditNote->user?->base_currency ?? 'EUR'));
                $issueDate = Carbon::parse((string) $validated['issue_date']);
                $exchangeRate = $currencyConversionService->rateFor(
                    $currency,
                    $baseCurrency,
                    $issueDate
                );
                $baseCurrencyTotal = $currencyConversionService->convert(
                    (float) $calculation['total'],
                    $currency,
                    $baseCurrency,
                    $issueDate
                );

                $creditNote->update([
                    'issue_date' => $issueDate->toDateString(),
                    'subtotal' => $calculation['subtotal'],
                    'tax_amount' => $calculation['tax_amount'],
                    'total' => $calculation['total'],
                    'currency' => $currency,
                    'base_currency' => $baseCurrency,
                    'exchange_rate' => $exchangeRate,
                    'base_currency_total' => $baseCurrencyTotal,
                    'reason' => $validated['reason'] ?? null,
                ]);

                $creditNote->lineItems()->delete();

                foreach ($lineItems as $index => $lineItem) {
                    $creditNote->lineItems()->create([
                        'description' => $lineItem['description'],
                        'quantity' => $lineItem['quantity'],
                        'unit_price' => $lineItem['unit_price'],
                        'vat_rate' => $lineItem['vat_rate'],
                        'sort_order' => $index,
                    ]);
                }
            });
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $creditNote->refresh()->load(['client', 'invoice', 'lineItems']);

        return $this->success(new CreditNoteResource($creditNote), 'Credit note updated successfully');
    }

    public function destroy(CreditNote $creditNote): JsonResponse
    {
        Gate::authorize('delete', $creditNote);

        if ($creditNote->status !== 'draft') {
            return $this->error('Only draft credit notes can be deleted', 422);
        }

        $creditNote->delete();

        return $this->success(null, 'Credit note deleted successfully');
    }

    public function send(CreditNote $creditNote): JsonResponse
    {
        Gate::authorize('update', $creditNote);

        if (! $creditNote->canTransitionTo('sent')) {
            return $this->error('Invalid credit note status transition', 422);
        }

        $creditNote->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        SendCreditNoteJob::dispatch($creditNote->id);

        return $this->success(new CreditNoteResource($creditNote->fresh(['client', 'invoice', 'lineItems'])), 'Credit note sent successfully');
    }

    public function apply(CreditNote $creditNote, ApplyCreditNoteService $service): JsonResponse
    {
        Gate::authorize('update', $creditNote);

        if ($creditNote->status === 'applied') {
            return $this->error('Credit note has already been applied', 422);
        }

        if (! $creditNote->canTransitionTo('applied')) {
            return $this->error('Invalid credit note status transition', 422);
        }

        try {
            $updated = $service->apply($creditNote);
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success(new CreditNoteResource($updated), 'Credit note applied successfully');
    }

    public function pdf(CreditNote $creditNote, CreditNotePdfService $pdfService): Response
    {
        Gate::authorize('view', $creditNote);

        $pdf = $pdfService->generate($creditNote);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$creditNote->number.'.pdf"',
        ]);
    }
}
