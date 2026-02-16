<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Invoices\StoreInvoiceRequest;
use App\Http\Requests\Api\V1\Invoices\UpdateInvoiceRequest;
use App\Http\Resources\Api\V1\Invoices\InvoiceResource;
use App\Jobs\SendInvoiceJob;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceCalculationService;
use App\Services\ReferenceGenerator;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class InvoiceController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Invoice::query()
            ->where('user_id', $user->id)
            ->with(['client', 'project']);

        if ($request->filled('status') && is_string($request->input('status'))) {
            $query->byStatus($request->input('status'));
        }

        if ($request->filled('client_id') && is_string($request->input('client_id'))) {
            $query->byClient($request->input('client_id'));
        }

        if ($request->filled('project_id') && is_string($request->input('project_id'))) {
            $query->where('project_id', $request->input('project_id'));
        }

        if ($request->filled('date_from') && is_string($request->input('date_from'))) {
            $query->whereDate('issue_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to') && is_string($request->input('date_to'))) {
            $query->whereDate('issue_date', '<=', $request->input('date_to'));
        }

        $sortField = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortFields = ['number', 'status', 'issue_date', 'due_date', 'total', 'created_at'];
        if (is_string($sortField) && in_array($sortField, $allowedSortFields, true)) {
            $query->orderBy($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $invoices = $query->paginate((int) ($request->input('per_page', 15)));

        /** @var array<string, mixed> $collectionPayload */
        $collectionPayload = InvoiceResource::collection($invoices)->response()->getData(true);

        return $this->success([
            'data' => $collectionPayload['data'] ?? [],
            'current_page' => $invoices->currentPage(),
            'per_page' => $invoices->perPage(),
            'total' => $invoices->total(),
            'last_page' => $invoices->lastPage(),
        ], 'Invoices retrieved successfully');
    }

    public function store(StoreInvoiceRequest $request, InvoiceCalculationService $calculationService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        /** @var Invoice $invoice */
        $invoice = DB::transaction(function () use ($validated, $user, $calculationService): Invoice {
            /** @var array<int, array<string, mixed>> $lineItems */
            $lineItems = $validated['line_items'];

            $calculation = $calculationService->calculate(
                $lineItems,
                $validated['discount_type'] ?? null,
                $validated['discount_value'] ?? null,
            );

            $invoice = Invoice::query()->create([
                'user_id' => $user->id,
                'client_id' => $validated['client_id'],
                'project_id' => $validated['project_id'] ?? null,
                'number' => ReferenceGenerator::generate('invoices', 'FAC'),
                'status' => 'draft',
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'subtotal' => $calculation['subtotal'],
                'tax_amount' => $calculation['tax_amount'],
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => $validated['discount_value'] ?? null,
                'discount_amount' => $calculation['discount_amount'],
                'total' => $calculation['total'],
                'currency' => $validated['currency'] ?? 'EUR',
                'notes' => $validated['notes'] ?? null,
                'payment_terms' => ($user->payment_terms_days ?? 30).' days',
            ]);

            foreach ($lineItems as $index => $lineItem) {
                $invoice->lineItems()->create([
                    'description' => $lineItem['description'],
                    'quantity' => $lineItem['quantity'],
                    'unit_price' => $lineItem['unit_price'],
                    'vat_rate' => $lineItem['vat_rate'],
                    'sort_order' => $index,
                ]);
            }

            return $invoice;
        });

        $invoice->load(['client', 'project', 'lineItems', 'payments', 'creditNotes']);

        return $this->success(new InvoiceResource($invoice), 'Invoice created successfully', 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        Gate::authorize('view', $invoice);

        $invoice->load(['client', 'project', 'lineItems', 'payments', 'creditNotes']);

        return $this->success(new InvoiceResource($invoice), 'Invoice retrieved successfully');
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice, InvoiceCalculationService $calculationService): JsonResponse
    {
        Gate::authorize('update', $invoice);

        if ($invoice->status !== 'draft') {
            return $this->error('Only draft invoices can be updated', 422);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($invoice, $validated, $calculationService): void {
            /** @var array<int, array<string, mixed>> $lineItems */
            $lineItems = $validated['line_items'];

            $calculation = $calculationService->calculate(
                $lineItems,
                $validated['discount_type'] ?? null,
                $validated['discount_value'] ?? null,
            );

            $invoice->update([
                'client_id' => $validated['client_id'],
                'project_id' => $validated['project_id'] ?? null,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'subtotal' => $calculation['subtotal'],
                'tax_amount' => $calculation['tax_amount'],
                'discount_type' => $validated['discount_type'] ?? null,
                'discount_value' => $validated['discount_value'] ?? null,
                'discount_amount' => $calculation['discount_amount'],
                'total' => $calculation['total'],
                'currency' => $validated['currency'] ?? $invoice->currency,
                'notes' => $validated['notes'] ?? null,
            ]);

            $invoice->lineItems()->delete();

            foreach ($lineItems as $index => $lineItem) {
                $invoice->lineItems()->create([
                    'description' => $lineItem['description'],
                    'quantity' => $lineItem['quantity'],
                    'unit_price' => $lineItem['unit_price'],
                    'vat_rate' => $lineItem['vat_rate'],
                    'sort_order' => $index,
                ]);
            }
        });

        $invoice->refresh()->load(['client', 'project', 'lineItems', 'payments', 'creditNotes']);

        return $this->success(new InvoiceResource($invoice), 'Invoice updated successfully');
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        Gate::authorize('delete', $invoice);

        if ($invoice->status !== 'draft') {
            return $this->error('Only draft invoices can be deleted', 422);
        }

        $invoice->delete();

        return $this->success(null, 'Invoice deleted successfully');
    }

    public function send(Invoice $invoice): JsonResponse
    {
        Gate::authorize('update', $invoice);

        if (! $invoice->canTransitionTo('sent')) {
            return $this->error('Invalid invoice status transition', 422);
        }

        $invoice->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        SendInvoiceJob::dispatch($invoice->id);

        return $this->success(new InvoiceResource($invoice->fresh(['client', 'project', 'lineItems', 'payments', 'creditNotes'])), 'Invoice sent successfully');
    }

    public function duplicate(Request $request, Invoice $invoice, InvoiceCalculationService $calculationService): JsonResponse
    {
        Gate::authorize('view', $invoice);

        /** @var User $user */
        $user = $request->user();

        $invoice->load('lineItems');

        $lineItems = $invoice->lineItems
            ->sortBy('sort_order')
            ->map(function ($lineItem): array {
                return [
                    'description' => $lineItem->description,
                    'quantity' => (float) $lineItem->quantity,
                    'unit_price' => (float) $lineItem->unit_price,
                    'vat_rate' => (float) $lineItem->vat_rate,
                ];
            })
            ->values()
            ->all();

        /** @var Invoice $clone */
        $clone = DB::transaction(function () use ($invoice, $lineItems, $user, $calculationService): Invoice {
            $calculation = $calculationService->calculate(
                $lineItems,
                $invoice->discount_type,
                $invoice->discount_value,
            );

            $clone = Invoice::query()->create([
                'user_id' => $invoice->user_id,
                'client_id' => $invoice->client_id,
                'project_id' => $invoice->project_id,
                'number' => ReferenceGenerator::generate('invoices', 'FAC'),
                'status' => 'draft',
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays((int) ($user->payment_terms_days ?? 30))->toDateString(),
                'subtotal' => $calculation['subtotal'],
                'tax_amount' => $calculation['tax_amount'],
                'discount_type' => $invoice->discount_type,
                'discount_value' => $invoice->discount_value,
                'discount_amount' => $calculation['discount_amount'],
                'total' => $calculation['total'],
                'currency' => $invoice->currency,
                'notes' => $invoice->notes,
                'payment_terms' => ($user->payment_terms_days ?? 30).' days',
            ]);

            foreach ($lineItems as $index => $lineItem) {
                $clone->lineItems()->create([
                    'description' => $lineItem['description'],
                    'quantity' => $lineItem['quantity'],
                    'unit_price' => $lineItem['unit_price'],
                    'vat_rate' => $lineItem['vat_rate'],
                    'sort_order' => $index,
                ]);
            }

            return $clone;
        });

        return $this->success(new InvoiceResource($clone->load(['client', 'project', 'lineItems', 'payments', 'creditNotes'])), 'Invoice duplicated successfully', 201);
    }
}
