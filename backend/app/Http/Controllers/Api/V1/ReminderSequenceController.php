<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reminders\StoreReminderSequenceRequest;
use App\Http\Requests\Api\V1\Reminders\UpdateReminderSequenceRequest;
use App\Models\ReminderSequence;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReminderSequenceController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $items = ReminderSequence::query()
            ->where('user_id', $user->id)
            ->with('steps')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return $this->success($items, 'Reminder sequences retrieved successfully');
    }

    public function store(StoreReminderSequenceRequest $request): JsonResponse
    {
        Gate::authorize('create', ReminderSequence::class);

        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $sequence = DB::transaction(function () use ($validated, $user): ReminderSequence {
            if (($validated['is_default'] ?? false) === true) {
                ReminderSequence::query()
                    ->where('user_id', $user->id)
                    ->update(['is_default' => false]);
            }

            $sequence = ReminderSequence::query()->create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'is_default' => $validated['is_default'] ?? false,
            ]);

            /** @var list<array<string, mixed>> $steps */
            $steps = is_array($validated['steps']) ? $validated['steps'] : [];
            usort(
                $steps,
                static fn (array $left, array $right): int => (int) ($left['step_number'] ?? 0) <=> (int) ($right['step_number'] ?? 0)
            );

            $sequence->steps()->createMany($steps);

            return $sequence;
        });

        return $this->success($sequence->load('steps'), 'Reminder sequence created successfully', 201);
    }

    public function show(ReminderSequence $sequence): JsonResponse
    {
        Gate::authorize('view', $sequence);

        return $this->success($sequence->load('steps'), 'Reminder sequence retrieved successfully');
    }

    public function update(UpdateReminderSequenceRequest $request, ReminderSequence $sequence): JsonResponse
    {
        Gate::authorize('update', $sequence);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $sequence): void {
            if (($validated['is_default'] ?? false) === true) {
                ReminderSequence::query()
                    ->where('user_id', $sequence->user_id)
                    ->where('id', '!=', $sequence->id)
                    ->update(['is_default' => false]);
            }

            $sequence->update([
                'name' => $validated['name'] ?? $sequence->name,
                'description' => $validated['description'] ?? $sequence->description,
                'is_active' => $validated['is_active'] ?? $sequence->is_active,
                'is_default' => $validated['is_default'] ?? $sequence->is_default,
            ]);

            if (isset($validated['steps']) && is_array($validated['steps'])) {
                $sequence->steps()->delete();
                /** @var list<array<string, mixed>> $steps */
                $steps = $validated['steps'];
                usort(
                    $steps,
                    static fn (array $left, array $right): int => (int) ($left['step_number'] ?? 0) <=> (int) ($right['step_number'] ?? 0)
                );
                $sequence->steps()->createMany($steps);
            }
        });

        return $this->success($sequence->fresh('steps'), 'Reminder sequence updated successfully');
    }

    public function destroy(ReminderSequence $sequence): JsonResponse
    {
        Gate::authorize('delete', $sequence);
        $sequence->delete();

        return response()->json(null, 204);
    }

    public function setDefault(ReminderSequence $sequence): JsonResponse
    {
        Gate::authorize('update', $sequence);

        DB::transaction(function () use ($sequence): void {
            ReminderSequence::query()
                ->where('user_id', $sequence->user_id)
                ->update(['is_default' => false]);

            $sequence->update(['is_default' => true]);
        });

        return $this->success($sequence->fresh('steps'), 'Default reminder sequence updated');
    }
}
