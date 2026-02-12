<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\CreateContactRequest;
use App\Http\Requests\Contact\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Client;
use App\Models\Contact;
use App\Services\ReferenceGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

final class ContactController extends Controller
{
    public function __construct(
        private ReferenceGeneratorService $referenceGenerator
    ) {}

    /**
     * Store a newly created contact for a client.
     */
    public function store(CreateContactRequest $request, Client $client): JsonResponse
    {
        $validated = $request->validated();

        // If this is set as primary, remove primary from other contacts
        if (($validated['is_primary'] ?? false) === true) {
            $client->contacts()->update(['is_primary' => false]);
        }

        $contact = $client->contacts()->create([
            'id' => $this->referenceGenerator->generateUuid(),
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'position' => $validated['position'] ?? null,
            'is_primary' => $validated['is_primary'] ?? false,
        ]);

        // Log activity
        $client->activities()->create([
            'id' => $this->referenceGenerator->generateUuid(),
            'user_id' => Auth::id(),
            'type' => 'system',
            'description' => "Contact {$contact->name} was added to client {$client->name}",
        ]);

        return (new ContactResource($contact))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified contact.
     */
    public function show(Contact $contact): ContactResource
    {
        return new ContactResource($contact);
    }

    /**
     * Update the specified contact.
     */
    public function update(UpdateContactRequest $request, Contact $contact): JsonResponse
    {
        $validated = $request->validated();

        // If this is set as primary, remove primary from other contacts
        if (($validated['is_primary'] ?? false) === true && ! $contact->is_primary) {
            $contact->client->contacts()->where('id', '!=', $contact->id)->update(['is_primary' => false]);
        }

        $contact->update([
            'name' => $validated['name'] ?? $contact->name,
            'email' => $validated['email'] ?? $contact->email,
            'phone' => $validated['phone'] ?? $contact->phone,
            'position' => $validated['position'] ?? $contact->position,
            'is_primary' => $validated['is_primary'] ?? $contact->is_primary,
        ]);

        // Log activity
        $contact->client->activities()->create([
            'id' => $this->referenceGenerator->generateUuid(),
            'user_id' => Auth::id(),
            'type' => 'system',
            'description' => "Contact {$contact->name} was updated",
        ]);

        return (new ContactResource($contact->fresh()))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Remove the specified contact.
     */
    public function destroy(Contact $contact): JsonResponse
    {
        $client = $contact->client;
        $contactName = $contact->name;

        $contact->delete();

        // Log activity
        $client->activities()->create([
            'id' => $this->referenceGenerator->generateUuid(),
            'user_id' => Auth::id(),
            'type' => 'system',
            'description' => "Contact {$contactName} was removed from client {$client->name}",
        ]);

        return response()->json([
            'meta' => [
                'message' => 'Contact deleted successfully.',
            ],
        ], Response::HTTP_NO_CONTENT);
    }
}
