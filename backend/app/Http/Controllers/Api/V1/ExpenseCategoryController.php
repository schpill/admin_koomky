<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $categories = ExpenseCategory::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return $this->success($categories, 'Expense categories retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);

        $category = ExpenseCategory::query()->create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'color' => $validated['color'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'is_default' => false,
        ]);

        return $this->success($category, 'Expense category created successfully', 201);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($expenseCategory->user_id !== $user->id) {
            return $this->error('Expense category not found', 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);

        $expenseCategory->update($validated);

        return $this->success($expenseCategory->fresh(), 'Expense category updated successfully');
    }

    public function destroy(Request $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($expenseCategory->user_id !== $user->id) {
            return $this->error('Expense category not found', 404);
        }

        if ($expenseCategory->is_default) {
            return $this->error('Default categories cannot be deleted', 422);
        }

        $expenseCategory->delete();

        return $this->success(null, 'Expense category deleted successfully');
    }
}
