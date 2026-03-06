<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DripEnrollment;
use App\Models\DripSequence;
use App\Models\Segment;
use App\Models\User;
use App\Services\DripEnrollmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DripSequenceController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly DripEnrollmentService $dripEnrollmentService) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $items = DripSequence::query()
            ->forUser($user)
            ->with(['steps', 'enrollments'])
            ->orderBy('name')
            ->get();

        return $this->success($items, 'Drip sequences retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', DripSequence::class);

        /** @var User $user */
        $user = $request->user();
        $validated = $this->validatePayload($request);

        $sequence = DB::transaction(function () use ($validated, $user): DripSequence {
            $sequence = DripSequence::query()->create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'trigger_event' => $validated['trigger_event'],
                'trigger_campaign_id' => $validated['trigger_campaign_id'] ?? null,
                'status' => $validated['status'],
                'settings' => $validated['settings'] ?? null,
            ]);

            $sequence->steps()->createMany($validated['steps']);

            return $sequence;
        });

        return $this->success($sequence->load(['steps', 'enrollments']), 'Drip sequence created successfully', 201);
    }

    public function show(DripSequence $dripSequence): JsonResponse
    {
        Gate::authorize('view', $dripSequence);

        return $this->success($dripSequence->load(['steps', 'enrollments.contact']), 'Drip sequence retrieved successfully');
    }

    public function update(Request $request, DripSequence $dripSequence): JsonResponse
    {
        Gate::authorize('update', $dripSequence);

        $validated = $this->validatePayload($request);

        DB::transaction(function () use ($validated, $dripSequence): void {
            $dripSequence->update([
                'name' => $validated['name'],
                'trigger_event' => $validated['trigger_event'],
                'trigger_campaign_id' => $validated['trigger_campaign_id'] ?? null,
                'status' => $validated['status'],
                'settings' => $validated['settings'] ?? null,
            ]);

            $dripSequence->steps()->delete();
            $dripSequence->steps()->createMany($validated['steps']);
        });

        return $this->success($dripSequence->fresh(['steps', 'enrollments']), 'Drip sequence updated successfully');
    }

    public function destroy(DripSequence $dripSequence): JsonResponse
    {
        Gate::authorize('delete', $dripSequence);

        $dripSequence->delete();

        return response()->json(null, 204);
    }

    public function enroll(Request $request, DripSequence $dripSequence): JsonResponse
    {
        Gate::authorize('update', $dripSequence);

        $validated = $request->validate([
            'contact_id' => ['required', 'uuid'],
        ]);

        $contact = \App\Models\Contact::query()->findOrFail((string) $validated['contact_id']);
        $enrollment = $this->dripEnrollmentService->enroll($contact, $dripSequence->load('user'));

        return $this->success($enrollment->load('contact'), 'Contact enrolled successfully');
    }

    public function enrollSegment(Request $request, DripSequence $dripSequence): JsonResponse
    {
        Gate::authorize('update', $dripSequence);

        $validated = $request->validate([
            'segment_id' => ['required', 'uuid'],
        ]);

        $segment = Segment::query()->findOrFail((string) $validated['segment_id']);
        $count = $this->dripEnrollmentService->enrollSegment($segment, $dripSequence->load('user'));

        return $this->success(['enrolled' => $count], 'Segment enrolled successfully');
    }

    public function pause(DripEnrollment $dripEnrollment): JsonResponse
    {
        Gate::authorize('update', $dripEnrollment->sequence);
        $dripEnrollment->update(['status' => 'paused']);

        return $this->success($dripEnrollment, 'Enrollment paused successfully');
    }

    public function resume(DripEnrollment $dripEnrollment): JsonResponse
    {
        Gate::authorize('update', $dripEnrollment->sequence);
        $dripEnrollment->update(['status' => 'active']);

        return $this->success($dripEnrollment, 'Enrollment resumed successfully');
    }

    public function cancel(DripEnrollment $dripEnrollment): JsonResponse
    {
        Gate::authorize('update', $dripEnrollment->sequence);
        $dripEnrollment->update(['status' => 'cancelled']);

        return $this->success($dripEnrollment, 'Enrollment cancelled successfully');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trigger_event' => ['required', Rule::in(['campaign_sent', 'contact_created', 'manual'])],
            'trigger_campaign_id' => ['nullable', 'uuid'],
            'status' => ['required', Rule::in(['active', 'paused', 'archived'])],
            'settings' => ['nullable', 'array'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.position' => ['required', 'integer', 'min:1'],
            'steps.*.delay_hours' => ['required', 'integer', 'min:0'],
            'steps.*.condition' => ['required', Rule::in(['none', 'if_opened', 'if_clicked', 'if_not_opened'])],
            'steps.*.subject' => ['required', 'string', 'max:255'],
            'steps.*.content' => ['required', 'string'],
            'steps.*.template_id' => ['nullable', 'uuid'],
        ]);
    }
}
