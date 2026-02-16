<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Clients\StoreClientRequest;
use App\Http\Requests\Api\V1\Clients\UpdateClientRequest;
use App\Http\Resources\Api\V1\Clients\ClientResource;
use App\Models\Client;
use App\Models\User;
use App\Services\ReferenceGenerator;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Client::where('user_id', $user->id)
            ->with(['contacts', 'tags']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Include trashed
        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        // Sorting
        $sortField = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortFields = ['name', 'reference', 'email', 'status', 'created_at'];
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $clients = $query->paginate((int) ($request->per_page ?? 15));

        return $this->success(ClientResource::collection($clients)->response()->getData(true), 'Clients retrieved successfully');
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $reference = ReferenceGenerator::generate('clients', 'CLI');

        $client = Client::create([
            ...$request->validated(),
            'user_id' => $user->id,
            'reference' => $reference,
            'status' => $request->input('status', 'active'),
        ]);

        return $this->success(new ClientResource($client), 'Client created successfully', 201);
    }

    public function show(Client $client): JsonResponse
    {
        Gate::authorize('view', $client);

        $client->load(['contacts', 'tags', 'activities' => function ($query) {
            $query->latest()->take(20);
        }]);

        return $this->success(new ClientResource($client), 'Client retrieved successfully');
    }

    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        Gate::authorize('update', $client);

        $client->update($request->validated());

        return $this->success(new ClientResource($client), 'Client updated successfully');
    }

    public function destroy(Client $client): JsonResponse
    {
        Gate::authorize('delete', $client);

        $client->delete();

        return $this->success(null, 'Client archived successfully');
    }

    public function restore(string $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $client = Client::onlyTrashed()
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        Gate::authorize('restore', $client);

        $client->restore();

        return $this->success(new ClientResource($client), 'Client restored successfully');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        $clients = Client::where('user_id', $user->id)->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="clients.csv"',
        ];

        return response()->stream(function () use ($clients) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fputcsv($handle, ['Reference', 'Name', 'Email', 'Phone', 'City', 'Country', 'Status']);

            foreach ($clients as $client) {
                fputcsv($handle, [
                    $client->reference,
                    $client->name,
                    $client->email,
                    $client->phone,
                    $client->city,
                    $client->country,
                    $client->status,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function importCsv(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        if (! $file instanceof \Illuminate\Http\UploadedFile) {
            return $this->error('Invalid file', 422);
        }

        $path = $file->getRealPath();
        $fileContent = file($path);
        if ($fileContent === false) {
            return $this->error('Failed to read file', 422);
        }

        $data = array_map('str_getcsv', $fileContent);
        $header = array_shift($data);

        if (! $header) {
            return $this->error('Invalid CSV format', 422);
        }

        /** @var array<int, string> $normalizedHeader */
        $normalizedHeader = array_map(
            fn ($column): string => $this->normalizeCsvHeader($column),
            $header
        );

        $count = 0;
        foreach ($data as $row) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = array_pad($row, count($normalizedHeader), null);
            $paired = array_combine($normalizedHeader, array_slice($row, 0, count($normalizedHeader)));

            if ($paired === false) {
                continue;
            }

            /** @var array<string, string|null> $values */
            $values = $paired;

            $status = $this->csvValue($values, 'status');
            if (! in_array($status, ['active', 'inactive', 'archived'], true)) {
                $status = 'active';
            }

            Client::create([
                'user_id' => $user->id,
                'reference' => ReferenceGenerator::generate('clients', 'CLI'),
                'name' => $this->csvValue($values, 'name') ?? 'Unknown',
                'email' => $this->csvValue($values, 'email'),
                'phone' => $this->csvValue($values, 'phone'),
                'city' => $this->csvValue($values, 'city'),
                'country' => $this->csvValue($values, 'country'),
                'zip_code' => $this->csvValue($values, 'zip_code'),
                'status' => $status,
            ]);
            $count++;
        }

        return $this->success(['imported' => $count], "Imported {$count} clients successfully");
    }

    private function normalizeCsvHeader(mixed $header): string
    {
        $normalized = strtolower(trim((string) $header));
        $normalized = str_replace([' ', '-'], '_', $normalized);

        return match ($normalized) {
            'postal_code', 'postal', 'zip' => 'zip_code',
            'company_name', 'company' => 'name',
            default => $normalized,
        };
    }

    /**
     * @param  array<string, string|null>  $values
     */
    private function csvValue(array $values, string $key): ?string
    {
        if (! array_key_exists($key, $values)) {
            return null;
        }

        $value = $values[$key];
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
