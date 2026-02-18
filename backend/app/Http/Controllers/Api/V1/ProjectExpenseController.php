<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectExpenseController extends Controller
{
    use ApiResponse;

    public function index(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        /** @var User $user */
        $user = $request->user();

        $expenses = $project->expenses()
            ->where('user_id', $user->id)
            ->with(['category', 'client'])
            ->orderByDesc('date')
            ->get();

        return $this->success($expenses, 'Project expenses retrieved successfully');
    }
}
