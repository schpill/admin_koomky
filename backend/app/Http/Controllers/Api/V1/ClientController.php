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

        $clients = Client::where('user_id', $user->id)
            ->with(['contacts', 'tags'])
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                             ->orWhere('reference', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate((int)($request->per_page ?? 15));

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
        ]);

        return $this->success(new ClientResource($client), 'Client created successfully', 201);
    }

    public function show(Client $client): JsonResponse
    {
        Gate::authorize('view', $client);

        $client->load(['contacts', 'tags', 'activities' => function($query) {
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

        return $this->success(null, 'Client deleted successfully');
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
            fputcsv($handle, ['Reference', 'Name', 'Email', 'Phone', 'City', 'Country']);

            foreach ($clients as $client) {
                fputcsv($handle, [
                    $client->reference,
                    $client->name,
                    $client->email,
                    $client->phone,
                    $client->city,
                    $client->country,
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
        if (!$file instanceof \Illuminate\Http\UploadedFile) {
            return $this->error('Invalid file', 422);
        }

        $path = $file->getRealPath();
        $fileContent = file($path);
        if ($fileContent === false) {
            return $this->error('Failed to read file', 422);
        }

        $data = array_map('str_getcsv', $fileContent);
        $header = array_shift($data);

        if (!$header) {
            return $this->error('Invalid CSV format', 422);
        }

        $count = 0;
        foreach ($data as $row) {
            /** @var array<string, string|null> $values */
            $filteredHeader = array_filter($header, fn($h) => !is_null($h));
            $values = array_combine($filteredHeader, $row);
            
            Client::create([
                'user_id' => $user->id,
                'reference' => ReferenceGenerator::generate('clients', 'CLI'),
                'name' => $values['name'] ?? 'Unknown',
                'email' => $values['email'] ?? null,
                'phone' => $values['phone'] ?? null,
            ]);
            $count++;
        }

        return $this->success(['imported' => $count], "Imported {$count} clients successfully");
    }
}
