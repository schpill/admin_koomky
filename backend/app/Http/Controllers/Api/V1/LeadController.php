<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadAnalyticsService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Lead::where('user_id', $user->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $leads = $query->latest()->paginate((int) ($request->per_page ?? 15));

        return $this->success($leads, 'Leads retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'company_name' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'source' => 'sometimes|in:manual,referral,website,campaign,other',
            'estimated_value' => 'nullable|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'probability' => 'nullable|integer|min:0|max:100',
            'expected_close_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        /** @var User $user */
        $user = $request->user();

        $lead = Lead::create([
            'user_id' => $user->id,
            'company_name' => $request->company_name,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'source' => $request->source ?? 'manual',
            'status' => 'new',
            'estimated_value' => $request->estimated_value,
            'currency' => $request->currency ?? 'EUR',
            'probability' => $request->probability,
            'expected_close_date' => $request->expected_close_date,
            'notes' => $request->notes,
        ]);

        return $this->success($lead, 'Lead created successfully', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $lead = Lead::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $lead->load(['activities', 'wonClient']);

        return $this->success($lead, 'Lead retrieved successfully');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $lead = Lead::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $request->validate([
            'company_name' => 'sometimes|nullable|string|max:255',
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'email' => 'sometimes|nullable|email|max:255',
            'phone' => 'sometimes|nullable|string|max:50',
            'source' => 'sometimes|in:manual,referral,website,campaign,other',
            'estimated_value' => 'sometimes|nullable|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'probability' => 'sometimes|nullable|integer|min:0|max:100',
            'expected_close_date' => 'sometimes|nullable|date',
            'notes' => 'sometimes|nullable|string',
        ]);

        $lead->update($request->only([
            'company_name', 'first_name', 'last_name', 'email', 'phone',
            'source', 'estimated_value', 'currency', 'probability',
            'expected_close_date', 'notes',
        ]));

        return $this->success($lead, 'Lead updated successfully');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $lead = Lead::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $lead->delete();

        return $this->success(null, 'Lead deleted successfully');
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $lead = Lead::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $request->validate([
            'status' => 'required|in:new,contacted,qualified,proposal_sent,negotiating,won,lost',
            'lost_reason' => 'required_if:status,lost|nullable|string|max:500',
        ]);

        $data = ['status' => $request->status];

        if ($request->status === 'won') {
            $data['probability'] = 100;
        } elseif ($request->status === 'lost') {
            $data['probability'] = 0;
            $data['lost_reason'] = $request->lost_reason;
        }

        $lead->update($data);

        return $this->success($lead, 'Lead status updated successfully');
    }

    public function updatePosition(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $lead = Lead::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $request->validate([
            'position' => 'required|integer|min:0',
        ]);

        $lead->update(['pipeline_position' => $request->position]);

        return $this->success($lead, 'Lead position updated successfully');
    }

    public function pipeline(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $columns = ['new', 'contacted', 'qualified', 'proposal_sent', 'negotiating', 'won', 'lost'];

        $leads = Lead::where('user_id', $user->id)
            ->orderBy('pipeline_position')
            ->get()
            ->groupBy('status');

        $columnStats = [];
        $totalPipelineValue = 0;

        foreach ($columns as $column) {
            $columnLeads = $leads->get($column, collect());
            $columnValue = $columnLeads->sum('estimated_value');

            if (! in_array($column, ['won', 'lost'])) {
                $totalPipelineValue += $columnValue;
            }

            $columnStats[$column] = [
                'count' => $columnLeads->count(),
                'total_value' => (float) $columnValue,
            ];
        }

        return $this->success([
            'columns' => $columns,
            'leads' => $leads,
            'column_stats' => $columnStats,
            'total_pipeline_value' => (float) $totalPipelineValue,
        ], 'Pipeline retrieved successfully');
    }

    public function analytics(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $service = new LeadAnalyticsService;
        $analytics = $service->build($user);

        return $this->success($analytics, 'Lead analytics retrieved successfully');
    }
}
