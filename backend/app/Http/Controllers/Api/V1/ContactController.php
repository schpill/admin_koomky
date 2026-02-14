<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Contact;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ContactController extends Controller
{
    use ApiResponse;

    public function store(Request $request, Client $client): JsonResponse
    {
        Gate::authorize('update', $client);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $contact = DB::transaction(function () use ($client, $validated) {
            if (!empty($validated['is_primary'])) {
                Contact::where('client_id', $client->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            return $client->contacts()->create($validated);
        });

        return $this->success($contact, 'Contact added successfully', 201);
    }
}
