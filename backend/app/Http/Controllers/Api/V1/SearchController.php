<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Clients\ClientResource;
use App\Models\Client;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->get('q');

        if (empty($query)) {
            return $this->success([], 'No query provided');
        }

        // Search Clients
        $clients = Client::search($query)
            ->where('user_id', $request->user()->id)
            ->take(5)
            ->get();

        return $this->success([
            'clients' => ClientResource::collection($clients),
            // We can add other models here later (Invoices, Projects...)
        ], 'Search results');
    }
}
