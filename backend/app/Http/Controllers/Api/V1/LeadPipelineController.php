<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadPipelineController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $columns = ['new', 'contacted', 'qualified', 'proposal_sent', 'negotiating', 'won', 'lost'];

        $leads = \App\Models\Lead::where('user_id', $user->id)
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
}
