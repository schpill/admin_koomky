<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadActivityController extends Controller
{
    use ApiResponse;

    public function index(Request $request, string $leadId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $lead = Lead::where('user_id', $user->id)
            ->where('id', $leadId)
            ->firstOrFail();

        $activities = $lead->activities()->latest()->get();

        return $this->success($activities, 'Lead activities retrieved successfully');
    }

    public function store(Request $request, string $leadId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $lead = Lead::where('user_id', $user->id)
            ->where('id', $leadId)
            ->firstOrFail();

        $request->validate([
            'type' => 'required|in:call,email,meeting,note,follow_up',
            'content' => 'required|string',
            'scheduled_at' => 'required_if:type,follow_up|nullable|date',
        ]);

        $activity = LeadActivity::create([
            'lead_id' => $lead->id,
            'type' => $request->type,
            'content' => $request->content,
            'scheduled_at' => $request->scheduled_at,
        ]);

        return $this->success($activity, 'Lead activity created successfully', 201);
    }

    public function destroy(Request $request, string $leadId, string $activityId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $lead = Lead::where('user_id', $user->id)
            ->where('id', $leadId)
            ->firstOrFail();

        $activity = $lead->activities()->where('id', $activityId)->firstOrFail();

        $activity->delete();

        return $this->success(null, 'Lead activity deleted successfully');
    }
}
