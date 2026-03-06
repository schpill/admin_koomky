<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Support\FrenchDepartments;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProspectMetaController extends Controller
{
    use ApiResponse;

    public function industries(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $items = Client::query()
            ->where('user_id', $user->id)
            ->whereNotNull('industry')
            ->distinct()
            ->orderBy('industry')
            ->pluck('industry')
            ->values()
            ->all();

        return $this->success($items, 'Prospect industries retrieved successfully');
    }

    public function departments(): JsonResponse
    {
        return $this->success(FrenchDepartments::all(), 'Prospect departments retrieved successfully');
    }
}
