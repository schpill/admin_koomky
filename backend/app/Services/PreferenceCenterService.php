<?php

namespace App\Services;

use App\Models\CommunicationPreference;
use App\Models\Contact;
use Illuminate\Support\Collection;

class PreferenceCenterService
{
    /** @var list<string> */
    private const CATEGORIES = ['newsletter', 'promotional', 'transactional'];

    /**
     * @return Collection<int, CommunicationPreference>
     */
    public function getPreferences(Contact $contact): Collection
    {
        $this->ensurePreferencesExist($contact);

        /** @var Collection<int, CommunicationPreference> $preferences */
        $preferences = $contact->communicationPreferences()
            ->orderByRaw("CASE category WHEN 'newsletter' THEN 1 WHEN 'promotional' THEN 2 ELSE 3 END")
            ->get();

        return $preferences;
    }

    public function updatePreference(Contact $contact, string $category, bool $subscribed): void
    {
        if (! in_array($category, self::CATEGORIES, true)) {
            return;
        }

        $this->ensurePreferencesExist($contact);

        $contact->communicationPreferences()->updateOrCreate(
            ['category' => $category],
            [
                'user_id' => $contact->client?->user_id,
                'subscribed' => $subscribed,
            ]
        );
    }

    public function isAllowed(Contact $contact, string $category): bool
    {
        if ($category === 'transactional') {
            return true;
        }

        if (! in_array($category, self::CATEGORIES, true)) {
            return true;
        }

        $this->ensurePreferencesExist($contact);

        return (bool) $contact->communicationPreferences()
            ->where('category', $category)
            ->value('subscribed');
    }

    private function ensurePreferencesExist(Contact $contact): void
    {
        $existingCategories = $contact->communicationPreferences()
            ->pluck('category')
            ->all();

        foreach (self::CATEGORIES as $category) {
            if (in_array($category, $existingCategories, true)) {
                continue;
            }

            $contact->communicationPreferences()->create([
                'user_id' => $contact->client?->user_id,
                'category' => $category,
                'subscribed' => true,
            ]);
        }
    }
}
