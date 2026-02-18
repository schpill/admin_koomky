<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Expenses\StoreExpenseRequest;
use App\Http\Requests\Api\V1\Expenses\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\User;
use App\Services\CurrencyConversionService;
use App\Services\ExpenseReceiptService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Expense::query()
            ->where('user_id', $user->id)
            ->with(['category', 'project', 'client']);

        if ($request->filled('date_from') && is_string($request->input('date_from'))) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to') && is_string($request->input('date_to'))) {
            $query->whereDate('date', '<=', $request->input('date_to'));
        }

        if ($request->filled('expense_category_id') && is_string($request->input('expense_category_id'))) {
            $query->where('expense_category_id', $request->input('expense_category_id'));
        }

        if ($request->filled('project_id') && is_string($request->input('project_id'))) {
            $query->where('project_id', $request->input('project_id'));
        }

        if ($request->filled('client_id') && is_string($request->input('client_id'))) {
            $query->where('client_id', $request->input('client_id'));
        }

        if ($request->filled('status') && is_string($request->input('status'))) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('billable')) {
            $billable = filter_var($request->input('billable'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($billable !== null) {
                $query->where('is_billable', $billable);
            }
        }

        if ($request->filled('q') && is_string($request->input('q'))) {
            $term = trim($request->input('q'));
            if ($term !== '') {
                $query->where(function ($builder) use ($term): void {
                    $builder
                        ->where('description', 'like', '%'.$term.'%')
                        ->orWhere('vendor', 'like', '%'.$term.'%')
                        ->orWhere('reference', 'like', '%'.$term.'%')
                        ->orWhere('notes', 'like', '%'.$term.'%');
                });
            }
        }

        $expenses = $query->orderByDesc('date')->paginate((int) $request->input('per_page', 15));

        return $this->success([
            'data' => $expenses->items(),
            'current_page' => $expenses->currentPage(),
            'per_page' => $expenses->perPage(),
            'total' => $expenses->total(),
            'last_page' => $expenses->lastPage(),
        ], 'Expenses retrieved successfully');
    }

    public function store(
        StoreExpenseRequest $request,
        CurrencyConversionService $currencyConversionService,
        ExpenseReceiptService $expenseReceiptService,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        $currency = strtoupper((string) $validated['currency']);
        $baseCurrency = strtoupper((string) ($user->base_currency ?? 'EUR'));
        $date = Carbon::parse((string) $validated['date']);

        try {
            $baseCurrencyAmount = $currencyConversionService->convert(
                (float) $validated['amount'],
                $currency,
                $baseCurrency,
                $date
            );
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        /** @var Expense $expense */
        $expense = Expense::query()->create([
            ...$validated,
            'user_id' => $user->id,
            'currency' => $currency,
            'base_currency_amount' => $baseCurrencyAmount,
            'tax_amount' => $validated['tax_amount'] ?? 0,
            'tax_rate' => $validated['tax_rate'] ?? null,
            'is_billable' => (bool) ($validated['is_billable'] ?? false),
            'is_reimbursable' => (bool) ($validated['is_reimbursable'] ?? false),
        ]);

        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt');
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $expenseReceiptService->upload($expense, $file);
            }
        }

        $expense->refresh()->load(['category', 'project', 'client']);

        return $this->success($expense, 'Expense created successfully', 201);
    }

    public function show(Expense $expense): JsonResponse
    {
        Gate::authorize('view', $expense);

        $expense->load(['category', 'project', 'client']);

        return $this->success($expense, 'Expense retrieved successfully');
    }

    public function update(
        UpdateExpenseRequest $request,
        Expense $expense,
        CurrencyConversionService $currencyConversionService,
    ): JsonResponse {
        Gate::authorize('update', $expense);

        $validated = $request->validated();
        /** @var User $user */
        $user = $request->user();

        $currency = strtoupper((string) $validated['currency']);
        $baseCurrency = strtoupper((string) ($user->base_currency ?? 'EUR'));
        $date = Carbon::parse((string) $validated['date']);

        try {
            $baseCurrencyAmount = $currencyConversionService->convert(
                (float) $validated['amount'],
                $currency,
                $baseCurrency,
                $date
            );
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $expense->update([
            ...$validated,
            'currency' => $currency,
            'base_currency_amount' => $baseCurrencyAmount,
            'tax_amount' => $validated['tax_amount'] ?? 0,
            'tax_rate' => $validated['tax_rate'] ?? null,
            'is_billable' => (bool) ($validated['is_billable'] ?? false),
            'is_reimbursable' => (bool) ($validated['is_reimbursable'] ?? false),
        ]);

        $expense->refresh()->load(['category', 'project', 'client']);

        return $this->success($expense, 'Expense updated successfully');
    }

    public function destroy(Expense $expense, ExpenseReceiptService $expenseReceiptService): JsonResponse
    {
        Gate::authorize('delete', $expense);

        $expenseReceiptService->delete($expense);
        $expense->delete();

        return $this->success(null, 'Expense deleted successfully');
    }

    public function uploadReceipt(
        Request $request,
        Expense $expense,
        ExpenseReceiptService $expenseReceiptService,
    ): JsonResponse {
        Gate::authorize('update', $expense);

        $validated = $request->validate([
            'receipt' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        $file = $validated['receipt'];
        if (! $file instanceof \Illuminate\Http\UploadedFile) {
            return $this->error('Invalid uploaded receipt', 422);
        }

        try {
            $expenseReceiptService->upload($expense, $file);
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $expense->refresh();

        return $this->success($expense, 'Expense receipt uploaded successfully');
    }

    public function downloadReceipt(Expense $expense): StreamedResponse|JsonResponse
    {
        Gate::authorize('view', $expense);

        if (! is_string($expense->receipt_path) || $expense->receipt_path === '') {
            return $this->error('Receipt not found', 404);
        }

        $disk = Storage::disk('receipts');
        if (! $disk->exists($expense->receipt_path)) {
            return $this->error('Receipt not found', 404);
        }

        return $disk->download(
            $expense->receipt_path,
            $expense->receipt_filename ?? basename($expense->receipt_path)
        );
    }
}
