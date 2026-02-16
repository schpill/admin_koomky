<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Clients\ClientResource;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Task;
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

        if (empty($query) || ! is_string($query)) {
            return $this->success([], 'No query provided');
        }

        $searchTerm = trim($query);

        // Search Clients
        $clients = Client::query()
            ->where('user_id', $user->id)
            ->where(function ($builder) use ($searchTerm): void {
                $builder
                    ->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('email', 'like', '%'.$searchTerm.'%')
                    ->orWhere('reference', 'like', '%'.$searchTerm.'%');
            })
            ->take(5)
            ->get();

        // Search Projects
        $projects = Project::query()
            ->where('user_id', $user->id)
            ->where(function ($builder) use ($searchTerm): void {
                $builder
                    ->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('reference', 'like', '%'.$searchTerm.'%')
                    ->orWhere('description', 'like', '%'.$searchTerm.'%');
            })
            ->take(5)
            ->get();

        // Search Tasks
        $tasks = Task::query()
            ->whereHas('project', function ($builder) use ($user): void {
                $builder->where('user_id', $user->id);
            })
            ->where(function ($builder) use ($searchTerm): void {
                $builder
                    ->where('title', 'like', '%'.$searchTerm.'%')
                    ->orWhere('description', 'like', '%'.$searchTerm.'%');
            })
            ->take(5)
            ->get();

        $invoices = Invoice::query()
            ->where('user_id', $user->id)
            ->where(function ($builder) use ($searchTerm): void {
                $builder
                    ->where('number', 'like', '%'.$searchTerm.'%')
                    ->orWhere('notes', 'like', '%'.$searchTerm.'%')
                    ->orWhereHas('client', function ($clientQuery) use ($searchTerm): void {
                        $clientQuery->where('name', 'like', '%'.$searchTerm.'%');
                    });
            })
            ->with('client')
            ->take(5)
            ->get();

        $quotes = Quote::query()
            ->where('user_id', $user->id)
            ->where(function ($builder) use ($searchTerm): void {
                $builder
                    ->where('number', 'like', '%'.$searchTerm.'%')
                    ->orWhere('notes', 'like', '%'.$searchTerm.'%')
                    ->orWhereHas('client', function ($clientQuery) use ($searchTerm): void {
                        $clientQuery->where('name', 'like', '%'.$searchTerm.'%');
                    });
            })
            ->with('client')
            ->take(5)
            ->get();

        return $this->success([
            'clients' => ClientResource::collection($clients),
            'projects' => $projects,
            'tasks' => $tasks,
            'invoices' => $invoices,
            'quotes' => $quotes,
        ], 'Search results');
    }
}
