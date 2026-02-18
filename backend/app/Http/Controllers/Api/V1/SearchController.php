<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Clients\ClientResource;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Task;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

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

        $clients = $this->searchClients($user, $searchTerm);

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

        $expenses = Expense::query()
            ->where('user_id', $user->id)
            ->where(function ($builder) use ($searchTerm): void {
                $builder
                    ->where('description', 'like', '%'.$searchTerm.'%')
                    ->orWhere('vendor', 'like', '%'.$searchTerm.'%')
                    ->orWhere('reference', 'like', '%'.$searchTerm.'%')
                    ->orWhere('notes', 'like', '%'.$searchTerm.'%');
            })
            ->with('category')
            ->take(5)
            ->get();

        return $this->success([
            'clients' => ClientResource::collection($clients),
            'projects' => $projects,
            'tasks' => $tasks,
            'invoices' => $invoices,
            'quotes' => $quotes,
            'expenses' => $expenses,
        ], 'Search results');
    }

    private function shouldUseScout(): bool
    {
        return config('scout.driver') === 'meilisearch';
    }

    private function scoutForcedFailureEnabled(): bool
    {
        return (bool) config('scout.force_failure', false);
    }

    /**
     * @return EloquentCollection<int, Client>
     */
    private function searchClients(User $user, string $searchTerm): EloquentCollection
    {
        if ($this->shouldUseScout()) {
            try {
                if ($this->scoutForcedFailureEnabled()) {
                    throw new RuntimeException('Forced Meilisearch outage for fallback validation');
                }

                return Client::search($searchTerm)
                    ->query(fn ($query) => $query->where('user_id', $user->id))
                    ->take(5)
                    ->get();
            } catch (Throwable $exception) {
                Log::warning('search_fallback_database', [
                    'engine' => 'meilisearch',
                    'model' => Client::class,
                    'reason' => $exception->getMessage(),
                ]);
            }
        }

        return Client::query()
            ->where('user_id', $user->id)
            ->where(function ($builder) use ($searchTerm): void {
                $builder
                    ->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('email', 'like', '%'.$searchTerm.'%')
                    ->orWhere('reference', 'like', '%'.$searchTerm.'%');
            })
            ->take(5)
            ->get();
    }
}
