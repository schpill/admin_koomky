<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Contact;
use App\Services\PreferenceCenterService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PreferenceCenterController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly PreferenceCenterService $preferenceCenterService) {}

    public function show(Request $request, Contact $contact): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return $this->error('Invalid or expired preferences link', 403);
        }

        return $this->success([
            'contact_id' => $contact->id,
            'preferences' => $this->preferenceCenterService->getPreferences($contact)->values()->all(),
        ], 'Preferences retrieved successfully');
    }

    public function update(Request $request, Contact $contact): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return $this->error('Invalid or expired preferences link', 403);
        }

        $validated = $request->validate([
            'preferences' => ['required', 'array', 'min:1'],
            'preferences.*.category' => ['required', 'in:newsletter,promotional,transactional'],
            'preferences.*.subscribed' => ['required', 'boolean'],
        ]);

        /** @var array<int, array{category:string,subscribed:bool}> $preferences */
        $preferences = $validated['preferences'];

        foreach ($preferences as $preference) {
            $this->preferenceCenterService->updatePreference(
                $contact,
                (string) $preference['category'],
                (bool) $preference['subscribed']
            );
        }

        if ($contact->client !== null) {
            Activity::query()->create([
                'user_id' => $contact->client->user_id,
                'subject_id' => $contact->client->id,
                'subject_type' => $contact->client::class,
                'description' => 'Communication preferences updated',
                'metadata' => [
                    'contact_id' => $contact->id,
                    'categories' => array_map(
                        static fn (array $preference): string => $preference['category'],
                        $preferences
                    ),
                    'source' => 'preference_center',
                    'ip_address' => $request->ip(),
                ],
            ]);
        }

        return $this->success([
            'contact_id' => $contact->id,
            'preferences' => $this->preferenceCenterService->getPreferences($contact)->values()->all(),
        ], 'Preferences updated successfully');
    }
}
