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

    public function index(Client $client): JsonResponse
    {
        Gate::authorize('view', $client);

        return $this->success($client->contacts()->latest()->get(), 'Contacts retrieved successfully');
    }

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
            'email_consent' => ['nullable', 'boolean'],
            'email_consent_date' => ['nullable', 'date', 'required_if:email_consent,true'],
            'sms_consent' => ['nullable', 'boolean'],
            'sms_consent_date' => ['nullable', 'date', 'required_if:sms_consent,true'],
        ]);
        $validated = $this->normalizeConsentPayload($validated);

        $contact = DB::transaction(function () use ($client, $validated) {
            if (! empty($validated['is_primary'])) {
                Contact::where('client_id', $client->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            return $client->contacts()->create($validated);
        });

        return $this->success($contact, 'Contact added successfully', 201);
    }

    public function update(Request $request, Client $client, Contact $contact): JsonResponse
    {
        Gate::authorize('update', $client);
        $this->ensureContactBelongsToClient($client, $contact);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
            'email_consent' => ['nullable', 'boolean'],
            'email_consent_date' => ['nullable', 'date', 'required_if:email_consent,true'],
            'sms_consent' => ['nullable', 'boolean'],
            'sms_consent_date' => ['nullable', 'date', 'required_if:sms_consent,true'],
        ]);
        $validated = $this->normalizeConsentPayload($validated);

        DB::transaction(function () use ($client, $contact, $validated) {
            if (! empty($validated['is_primary'])) {
                Contact::where('client_id', $client->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $contact->update($validated);
        });

        return $this->success($contact, 'Contact updated successfully');
    }

    public function destroy(Client $client, Contact $contact): JsonResponse
    {
        Gate::authorize('update', $client);
        $this->ensureContactBelongsToClient($client, $contact);

        $contact->delete();

        return $this->success(null, 'Contact deleted successfully');
    }

    protected function ensureContactBelongsToClient(Client $client, Contact $contact): void
    {
        if ($contact->client_id !== $client->id) {
            abort(404, 'Contact not found for this client');
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function normalizeConsentPayload(array $validated): array
    {
        if (array_key_exists('email_consent', $validated) && $validated['email_consent'] !== true) {
            $validated['email_consent_date'] = null;
        }

        if (array_key_exists('sms_consent', $validated) && $validated['sms_consent'] !== true) {
            $validated['sms_consent_date'] = null;
        }

        return $validated;
    }
}
