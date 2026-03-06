<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ScoringRule;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScoringRuleController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $rules = ScoringRule::query()
            ->where('user_id', $user->id)
            ->orderBy('event')
            ->get();

        return $this->success($rules, 'Scoring rules retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'event' => ['required', 'string', 'max:50'],
            'points' => ['required', 'integer', 'between:-32768,32767'],
            'expiry_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rule = ScoringRule::query()->create([
            'user_id' => $user->id,
            'event' => $validated['event'],
            'points' => $validated['points'],
            'expiry_days' => $validated['expiry_days'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return $this->success($rule, 'Scoring rule created successfully', 201);
    }

    public function update(Request $request, string $ruleId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $rule = ScoringRule::query()
            ->where('user_id', $user->id)
            ->findOrFail($ruleId);

        $validated = $request->validate([
            'points' => ['sometimes', 'integer', 'between:-32768,32767'],
            'expiry_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $rule->update($validated);

        return $this->success($rule->fresh(), 'Scoring rule updated successfully');
    }

    public function destroy(Request $request, string $ruleId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $rule = ScoringRule::query()
            ->where('user_id', $user->id)
            ->findOrFail($ruleId);

        $rule->delete();

        return $this->success(null, 'Scoring rule deleted successfully');
    }
}
