<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $activities = $user->activities()
            ->when($request->subject_type, function ($query, $type) {
                $modelClass = "App\Models" . ucfirst($type);
                return $query->where('subject_type', $modelClass);
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return $this->success(
            ActivityResource::collection($activities)->response()->getData(true),
            'Activities retrieved successfully'
        );
    }
}
