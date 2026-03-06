<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EmailWarmupPlan;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmailWarmupPlanController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $plans = EmailWarmupPlan::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->get();

        return $this->success($plans, 'Warm-up plans retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate($this->rules());

        EmailWarmupPlan::query()
            ->activeForUser($user)
            ->update(['status' => 'paused']);

        $plan = EmailWarmupPlan::query()->create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'status' => $validated['status'] ?? 'active',
            'daily_volume_start' => $validated['daily_volume_start'],
            'daily_volume_max' => $validated['daily_volume_max'],
            'increment_percent' => $validated['increment_percent'] ?? 30,
            'current_day' => 0,
            'current_daily_limit' => $validated['daily_volume_start'],
            'started_at' => now(),
        ]);

        return $this->success($plan, 'Warm-up plan created successfully', 201);
    }

    public function update(Request $request, EmailWarmupPlan $plan): JsonResponse
    {
        $this->authorizePlan($request, $plan);

        $validated = $request->validate($this->rules(false));

        $plan->update($validated);

        return $this->success($plan->fresh(), 'Warm-up plan updated successfully');
    }

    public function destroy(Request $request, EmailWarmupPlan $plan): JsonResponse
    {
        $this->authorizePlan($request, $plan);

        $plan->delete();

        return $this->success(null, 'Warm-up plan deleted successfully');
    }

    public function pause(Request $request, EmailWarmupPlan $plan): JsonResponse
    {
        $this->authorizePlan($request, $plan);

        $plan->update(['status' => 'paused']);

        return $this->success($plan->fresh(), 'Warm-up plan paused successfully');
    }

    public function resume(Request $request, EmailWarmupPlan $plan): JsonResponse
    {
        $this->authorizePlan($request, $plan);

        /** @var User $user */
        $user = $request->user();

        EmailWarmupPlan::query()
            ->activeForUser($user)
            ->whereKeyNot($plan->id)
            ->update(['status' => 'paused']);

        $plan->update([
            'status' => 'active',
            'started_at' => $plan->started_at ?? now(),
        ]);

        return $this->success($plan->fresh(), 'Warm-up plan resumed successfully');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $required = true): array
    {
        $prefix = $required ? 'required|' : 'sometimes|';

        return [
            'name' => $prefix.'string|max:255',
            'status' => ['sometimes', Rule::in(['active', 'paused', 'completed'])],
            'daily_volume_start' => $prefix.'integer|min:1',
            'daily_volume_max' => $prefix.'integer|min:1',
            'increment_percent' => 'sometimes|integer|min:1|max:200',
            'current_day' => 'sometimes|integer|min:0',
            'current_daily_limit' => 'sometimes|integer|min:1',
            'started_at' => 'sometimes|date',
        ];
    }

    private function authorizePlan(Request $request, EmailWarmupPlan $plan): void
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($plan->user_id === $user->id, 404);
    }
}
