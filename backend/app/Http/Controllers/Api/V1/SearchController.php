<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Clients\ClientResource;
use App\Models\Client;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $query = $request->get('q');

        if (empty($query) || !is_string($query)) {
            return $this->success([], 'No query provided');
        }

        // Search Clients
        $clients = Client::search($query)
            ->where('user_id', $user->id)
            ->take(5)
            ->get();

        return $this->success([
            'clients' => ClientResource::collection($clients),
            // We can add other models here later (Invoices, Projects...)
        ], 'Search results');
    }
}
