<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Quotes\StoreQuoteRequest;
use App\Http\Requests\Api\V1\Quotes\UpdateQuoteRequest;
use App\Http\Resources\Api\V1\Invoices\InvoiceResource;
use App\Http\Resources\Api\V1\Quotes\QuoteResource;
use App\Jobs\SendQuoteJob;
use App\Models\Quote;
use App\Models\User;
use App\Services\ConvertQuoteToInvoiceService;
use App\Services\CurrencyConversionService;
use App\Services\InvoiceCalculationService;
use App\Services\QuotePdfService;
use App\Services\ReferenceGenerator;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class QuoteController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Quote::query()
            ->where('user_id', $user->id)
            ->with(['client', 'project', 'convertedInvoice']);

        if ($request->filled('status') && is_string($request->input('status'))) {
            $query->byStatus($request->input('status'));
        }

        if ($request->filled('client_id') && is_string($request->input('client_id'))) {
            $query->byClient($request->input('client_id'));
        }

        if ($request->filled('date_from') && is_string($request->input('date_from'))) {
            $query->whereDate('issue_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to') && is_string($request->input('date_to'))) {
            $query->whereDate('issue_date', '<=', $request->input('date_to'));
        }

        $sortField = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortFields = ['number', 'status', 'issue_date', 'valid_until', 'total', 'created_at'];
        if (is_string($sortField) && in_array($sortField, $allowedSortFields, true)) {
            $query->orderBy($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $quotes = $query->paginate((int) ($request->input('per_page', 15)));

        /** @var array<string, mixed> $collectionPayload */
        $collectionPayload = QuoteResource::collection($quotes)->response()->getData(true);

        return $this->success([
            'data' => $collectionPayload['data'] ?? [],
            'current_page' => $quotes->currentPage(),
            'per_page' => $quotes->perPage(),
            'total' => $quotes->total(),
            'last_page' => $quotes->lastPage(),
        ], 'Quotes retrieved successfully');
    }

    public function store(
        StoreQuoteRequest $request,
        InvoiceCalculationService $calculationService,
        CurrencyConversionService $currencyConversionService
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        try {
            /** @var Quote $quote */
            $quote = DB::transaction(function () use ($validated, $user, $calculationService, $currencyConversionService): Quote {
                /** @var array<int, array<string, mixed>> $lineItems */
                $lineItems = $validated['line_items'];
                $issueDate = Carbon::parse((string) $validated['issue_date']);
                $validUntil = array_key_exists('valid_until', $validated) && is_string($validated['valid_until'])
                    ? Carbon::parse($validated['valid_until'])
                    : $issueDate->copy()->addDays(30);
                $currency = strtoupper((string) ($validated['currency'] ?? 'EUR'));
                $baseCurrency = strtoupper((string) ($user->base_currency ?? 'EUR'));

                $calculation = $calculationService->calculate(
                    $lineItems,
                    $validated['discount_type'] ?? null,
                    $validated['discount_value'] ?? null,
                );

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

                $quote = Quote::query()->create([
                    'user_id' => $user->id,
                    'client_id' => $validated['client_id'],
                    'project_id' => $validated['project_id'] ?? null,
                    'number' => ReferenceGenerator::generate('quotes', 'DEV'),
                    'status' => 'draft',
                    'issue_date' => $issueDate->toDateString(),
                    'valid_until' => $validUntil->toDateString(),
                    'subtotal' => $calculation['subtotal'],
                    'tax_amount' => $calculation['tax_amount'],
                    'discount_type' => $validated['discount_type'] ?? null,
                    'discount_value' => $validated['discount_value'] ?? null,
                    'discount_amount' => $calculation['discount_amount'],
                    'total' => $calculation['total'],
                    'currency' => $currency,
                    'base_currency' => $baseCurrency,
                    'exchange_rate' => $exchangeRate,
                    'base_currency_total' => $baseCurrencyTotal,
                    'notes' => $validated['notes'] ?? null,
                ]);

                foreach ($lineItems as $index => $lineItem) {
                    $quote->lineItems()->create([
                        'description' => $lineItem['description'],
                        'quantity' => $lineItem['quantity'],
                        'unit_price' => $lineItem['unit_price'],
                        'vat_rate' => $lineItem['vat_rate'],
                        'sort_order' => $index,
                    ]);
                }

                return $quote;
            });
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $quote->load(['client', 'project', 'convertedInvoice', 'lineItems']);

        return $this->success(new QuoteResource($quote), 'Quote created successfully', 201);
    }

    public function show(Quote $quote): JsonResponse
    {
        Gate::authorize('view', $quote);

        $quote->load(['client', 'project', 'convertedInvoice', 'lineItems']);

        return $this->success(new QuoteResource($quote), 'Quote retrieved successfully');
    }

    public function update(
        UpdateQuoteRequest $request,
        Quote $quote,
        InvoiceCalculationService $calculationService,
        CurrencyConversionService $currencyConversionService
    ): JsonResponse {
        Gate::authorize('update', $quote);

        if ($quote->status !== 'draft') {
            return $this->error('Only draft quotes can be updated', 422);
        }

        $validated = $request->validated();

        if (
            array_key_exists('status', $validated)
            && is_string($validated['status'])
            && $validated['status'] !== $quote->status
            && ! $quote->canTransitionTo($validated['status'])
        ) {
            return $this->error('Invalid quote status transition', 422);
        }

        try {
            DB::transaction(function () use ($quote, $validated, $calculationService, $currencyConversionService): void {
                /** @var array<int, array<string, mixed>> $lineItems */
                $lineItems = $validated['line_items'];
                $issueDate = Carbon::parse((string) $validated['issue_date']);
                $validUntil = array_key_exists('valid_until', $validated) && is_string($validated['valid_until'])
                    ? Carbon::parse($validated['valid_until'])
                    : $issueDate->copy()->addDays(30);
                $currency = strtoupper((string) ($validated['currency'] ?? $quote->currency));
                $baseCurrency = strtoupper((string) ($quote->user?->base_currency ?? 'EUR'));

                $calculation = $calculationService->calculate(
                    $lineItems,
                    $validated['discount_type'] ?? null,
                    $validated['discount_value'] ?? null,
                );

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

                $quote->update([
                    'client_id' => $validated['client_id'],
                    'project_id' => $validated['project_id'] ?? null,
                    'issue_date' => $issueDate->toDateString(),
                    'valid_until' => $validUntil->toDateString(),
                    'subtotal' => $calculation['subtotal'],
                    'tax_amount' => $calculation['tax_amount'],
                    'discount_type' => $validated['discount_type'] ?? null,
                    'discount_value' => $validated['discount_value'] ?? null,
                    'discount_amount' => $calculation['discount_amount'],
                    'total' => $calculation['total'],
                    'currency' => $currency,
                    'base_currency' => $baseCurrency,
                    'exchange_rate' => $exchangeRate,
                    'base_currency_total' => $baseCurrencyTotal,
                    'notes' => $validated['notes'] ?? null,
                ]);

                $quote->lineItems()->delete();

                foreach ($lineItems as $index => $lineItem) {
                    $quote->lineItems()->create([
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

        $quote->refresh()->load(['client', 'project', 'convertedInvoice', 'lineItems']);

        return $this->success(new QuoteResource($quote), 'Quote updated successfully');
    }

    public function destroy(Quote $quote): JsonResponse
    {
        Gate::authorize('delete', $quote);

        if ($quote->status !== 'draft') {
            return $this->error('Only draft quotes can be deleted', 422);
        }

        $quote->delete();

        return $this->success(null, 'Quote deleted successfully');
    }

    public function send(Quote $quote): JsonResponse
    {
        Gate::authorize('update', $quote);

        if (! $quote->canTransitionTo('sent')) {
            return $this->error('Invalid quote status transition', 422);
        }

        $quote->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        SendQuoteJob::dispatch($quote->id);

        return $this->success(new QuoteResource($quote->fresh(['client', 'project', 'convertedInvoice', 'lineItems'])), 'Quote sent successfully');
    }

    public function accept(Quote $quote): JsonResponse
    {
        Gate::authorize('update', $quote);

        if (! $quote->canTransitionTo('accepted')) {
            return $this->error('Invalid quote status transition', 422);
        }

        $quote->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        return $this->success(new QuoteResource($quote->fresh(['client', 'project', 'convertedInvoice', 'lineItems'])), 'Quote accepted successfully');
    }

    public function reject(Quote $quote): JsonResponse
    {
        Gate::authorize('update', $quote);

        if (! $quote->canTransitionTo('rejected')) {
            return $this->error('Invalid quote status transition', 422);
        }

        $quote->update([
            'status' => 'rejected',
        ]);

        return $this->success(new QuoteResource($quote->fresh(['client', 'project', 'convertedInvoice', 'lineItems'])), 'Quote rejected successfully');
    }

    public function convert(Quote $quote, ConvertQuoteToInvoiceService $service): JsonResponse
    {
        Gate::authorize('update', $quote);

        if ($quote->converted_invoice_id !== null) {
            return $this->error('Quote has already been converted', 422);
        }

        if (in_array($quote->status, ['rejected', 'expired'], true)) {
            return $this->error('Only active quotes can be converted', 422);
        }

        $invoice = $service->convert($quote);
        $invoice->load(['client', 'project', 'lineItems', 'payments']);

        return $this->success(new InvoiceResource($invoice), 'Quote converted successfully', 201);
    }

    public function pdf(Quote $quote, QuotePdfService $pdfService): Response
    {
        Gate::authorize('view', $quote);

        $pdf = $pdfService->generate($quote);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$quote->number.'.pdf"',
        ]);
    }
}
