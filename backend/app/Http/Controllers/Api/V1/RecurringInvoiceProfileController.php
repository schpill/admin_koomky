<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RecurringInvoices\StoreRecurringInvoiceProfileRequest;
use App\Http\Requests\Api\V1\RecurringInvoices\UpdateRecurringInvoiceProfileRequest;
use App\Http\Resources\Api\V1\RecurringInvoices\RecurringInvoiceProfileResource;
use App\Models\RecurringInvoiceProfile;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RecurringInvoiceProfileController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = RecurringInvoiceProfile::query()
            ->where('user_id', $user->id)
            ->with('client');

        if ($request->filled('status') && is_string($request->input('status'))) {
            $query->where('status', $request->input('status'));
        }

        $profiles = $query
            ->orderBy('next_due_date')
            ->paginate((int) ($request->input('per_page', 15)));

        /** @var array<string, mixed> $collectionPayload */
        $collectionPayload = RecurringInvoiceProfileResource::collection($profiles)->response()->getData(true);

        return $this->success([
            'data' => $collectionPayload['data'] ?? [],
            'current_page' => $profiles->currentPage(),
            'per_page' => $profiles->perPage(),
            'total' => $profiles->total(),
            'last_page' => $profiles->lastPage(),
        ], 'Recurring invoice profiles retrieved successfully');
    }

    public function store(StoreRecurringInvoiceProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        $profile = RecurringInvoiceProfile::query()->create([
            'user_id' => $user->id,
            'client_id' => $validated['client_id'],
            'name' => $validated['name'],
            'frequency' => $validated['frequency'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'next_due_date' => $validated['next_due_date'] ?? $validated['start_date'],
            'day_of_month' => $validated['day_of_month'] ?? null,
            'line_items' => $validated['line_items'],
            'notes' => $validated['notes'] ?? null,
            'payment_terms_days' => $validated['payment_terms_days'] ?? (int) ($user->payment_terms_days ?? 30),
            'tax_rate' => $validated['tax_rate'] ?? null,
            'discount_percent' => $validated['discount_percent'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'max_occurrences' => $validated['max_occurrences'] ?? null,
            'auto_send' => $validated['auto_send'] ?? false,
            'currency' => $validated['currency'] ?? 'EUR',
        ]);

        $profile->load('client');

        return $this->success(new RecurringInvoiceProfileResource($profile), 'Recurring invoice profile created successfully', 201);
    }

    public function show(RecurringInvoiceProfile $recurring_invoice): JsonResponse
    {
        Gate::authorize('view', $recurring_invoice);

        $recurring_invoice->load([
            'client',
            'invoices' => fn ($query) => $query->latest()->limit(20),
        ]);

        return $this->success(new RecurringInvoiceProfileResource($recurring_invoice), 'Recurring invoice profile retrieved successfully');
    }

    public function update(UpdateRecurringInvoiceProfileRequest $request, RecurringInvoiceProfile $recurring_invoice): JsonResponse
    {
        Gate::authorize('update', $recurring_invoice);

        $validated = $request->validated();

        $recurring_invoice->update([
            'client_id' => $validated['client_id'],
            'name' => $validated['name'],
            'frequency' => $validated['frequency'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'next_due_date' => $validated['next_due_date'] ?? $validated['start_date'],
            'day_of_month' => $validated['day_of_month'] ?? null,
            'line_items' => $validated['line_items'],
            'notes' => $validated['notes'] ?? null,
            'payment_terms_days' => $validated['payment_terms_days'] ?? $recurring_invoice->payment_terms_days,
            'tax_rate' => $validated['tax_rate'] ?? null,
            'discount_percent' => $validated['discount_percent'] ?? null,
            'status' => $validated['status'] ?? $recurring_invoice->status,
            'max_occurrences' => $validated['max_occurrences'] ?? null,
            'auto_send' => $validated['auto_send'] ?? false,
            'currency' => $validated['currency'] ?? $recurring_invoice->currency,
        ]);

        return $this->success(new RecurringInvoiceProfileResource($recurring_invoice->fresh('client')), 'Recurring invoice profile updated successfully');
    }

    public function destroy(RecurringInvoiceProfile $recurring_invoice): JsonResponse
    {
        Gate::authorize('delete', $recurring_invoice);

        $recurring_invoice->delete();

        return $this->success(null, 'Recurring invoice profile deleted successfully');
    }

    public function pause(RecurringInvoiceProfile $recurring_invoice): JsonResponse
    {
        Gate::authorize('update', $recurring_invoice);

        $recurring_invoice->update(['status' => 'paused']);

        return $this->success(new RecurringInvoiceProfileResource($recurring_invoice), 'Recurring invoice profile paused successfully');
    }

    public function resume(RecurringInvoiceProfile $recurring_invoice): JsonResponse
    {
        Gate::authorize('update', $recurring_invoice);

        if (in_array($recurring_invoice->status, ['cancelled', 'completed'], true)) {
            return $this->error('Only paused recurring profiles can be resumed', 422);
        }

        $recurring_invoice->update(['status' => 'active']);

        return $this->success(new RecurringInvoiceProfileResource($recurring_invoice), 'Recurring invoice profile resumed successfully');
    }

    public function cancel(RecurringInvoiceProfile $recurring_invoice): JsonResponse
    {
        Gate::authorize('update', $recurring_invoice);

        $recurring_invoice->update(['status' => 'cancelled']);

        return $this->success(new RecurringInvoiceProfileResource($recurring_invoice), 'Recurring invoice profile cancelled successfully');
    }
}
