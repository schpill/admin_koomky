<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\CreateClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Resources\ClientCollection;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Services\ReferenceGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

final readonly class ClientController extends Controller {
    public function __construct(
        private ReferenceGeneratorService $referenceGenerator
    ) {}

    /**
     * Display a listing of clients.
     */
    public function index(Request $request): ClientCollection
    {
        $query = Client::query()
            ->where('user_id', Auth::id())
            ->with(['tags', 'contacts']);

        // Search
        if ($request->has('search')) {
            $query->search($request->input('search'));
        }

        // Filter by status
        if ($request->has('status')) {
            match ($request->input('status')) {
                'active' => $query->active(),
                'archived' => $query->archived(),
                default => null,
            };
        }

        // Filter by tag
        if ($request->has('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('name', $request->input('tag')));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->input('per_page', 15), 100);
        $clients = $query->paginate($perPage);

        return new ClientCollection($clients);
    }

    /**
     * Store a newly created client.
     */
    public function store(CreateClientRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $client = Client::create([
            'id' => $this->referenceGenerator->generateUuid(),
            'user_id' => Auth::id(),
            'reference' => $this->referenceGenerator->generateClientReference(Auth::user()),
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'company' => $validated['company'] ?? null,
            'vat_number' => $validated['vat_number'] ?? null,
            'website' => $validated['website'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'billing_address' => $validated['billing_address'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ]);

        // Attach tags if provided
        if (! empty($validated['tags'] ?? [])) {
            $this->syncTags($client, $validated['tags']);
        }

        // Create contacts if provided
        if (! empty($validated['contacts'] ?? [])) {
            foreach ($validated['contacts'] as $contactData) {
                $client->contacts()->create([
                    'id' => $this->referenceGenerator->generateUuid(),
                    'name' => $contactData['name'],
                    'email' => $contactData['email'] ?? null,
                    'phone' => $contactData['phone'] ?? null,
                    'position' => $contactData['position'] ?? null,
                    'is_primary' => $contactData['is_primary'] ?? false,
                ]);
            }
        }

        // Log activity
        $client->activities()->create([
            'id' => $this->referenceGenerator->generateUuid(),
            'user_id' => Auth::id(),
            'action' => 'created',
            'description' => "Client {$client->name} was created",
        ]);

        return (new ClientResource($client->load(['tags', 'contacts'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified client.
     */
    public function show(Client $client): ClientResource
    {
        $this->authorize('view', $client);

        return new ClientResource($client->load(['tags', 'contacts', 'activities']));
    }

    /**
     * Update the specified client.
     */
    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $this->authorize('update', $client);

        $validated = $request->validated();

        $client->update([
            'name' => $validated['name'] ?? $client->name,
            'email' => $validated['email'] ?? $client->email,
            'phone' => $validated['phone'] ?? $client->phone,
            'company' => $validated['company'] ?? $client->company,
            'vat_number' => $validated['vat_number'] ?? $client->vat_number,
            'website' => $validated['website'] ?? $client->website,
            'notes' => $validated['notes'] ?? $client->notes,
            'billing_address' => $validated['billing_address'] ?? $client->billing_address,
            'status' => $validated['status'] ?? $client->status,
        ]);

        // Sync tags if provided
        if (isset($validated['tags'])) {
            $this->syncTags($client, $validated['tags']);
        }

        // Update contacts if provided
        if (isset($validated['contacts'])) {
            $client->contacts()->delete();
            foreach ($validated['contacts'] as $contactData) {
                $client->contacts()->create([
                    'id' => $this->referenceGenerator->generateUuid(),
                    'name' => $contactData['name'],
                    'email' => $contactData['email'] ?? null,
                    'phone' => $contactData['phone'] ?? null,
                    'position' => $contactData['position'] ?? null,
                    'is_primary' => $contactData['is_primary'] ?? false,
                ]);
            }
        }

        // Log activity
        $client->activities()->create([
            'id' => $this->referenceGenerator->generateUuid(),
            'user_id' => Auth::id(),
            'action' => 'updated',
            'description' => "Client {$client->name} was updated",
        ]);

        return (new ClientResource($client->fresh()->load(['tags', 'contacts'])))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Remove the specified client.
     */
    public function destroy(Client $client): JsonResponse
    {
        $this->authorize('delete', $client);

        $client->delete();

        return response()->json([
            'meta' => [
                'message' => 'Client deleted successfully.',
            ],
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Archive the specified client.
     */
    public function archive(Client $client): JsonResponse
    {
        $this->authorize('update', $client);

        $client->update(['status' => 'archived']);

        $client->activities()->create([
            'id' => $this->referenceGenerator->generateUuid(),
            'user_id' => Auth::id(),
            'action' => 'archived',
            'description' => "Client {$client->name} was archived",
        ]);

        return (new ClientResource($client->load(['tags', 'contacts'])))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Restore (unarchive) the specified client.
     */
    public function restore(Client $client): JsonResponse
    {
        $this->authorize('update', $client);

        $client->update(['status' => 'active']);

        $client->activities()->create([
            'id' => $this->referenceGenerator->generateUuid(),
            'user_id' => Auth::id(),
            'action' => 'restored',
            'description' => "Client {$client->name} was restored",
        ]);

        return (new ClientResource($client->load(['tags', 'contacts'])))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Sync tags for a client.
     */
    protected function syncTags(Client $client, array $tags): void
    {
        $tagIds = collect($tags)->map(function ($tagName) {
            return \App\Models\Tag::firstOrCreate([
                'user_id' => Auth::id(),
                'name' => $tagName,
                'color' => $this->getRandomTagColor(),
            ])->id;
        });

        $client->tags()->sync($tagIds);
    }

    /**
     * Get a random tag color.
     */
    protected function getRandomTagColor(): string
    {
        $colors = ['blue', 'green', 'yellow', 'red', 'purple', 'pink', 'indigo'];
        return $colors[array_rand($colors)];
    }
}
