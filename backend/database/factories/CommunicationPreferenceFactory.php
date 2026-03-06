<?php

namespace Database\Factories;

use App\Models\CommunicationPreference;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunicationPreference>
 */
class CommunicationPreferenceFactory extends Factory
{
    protected $model = CommunicationPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'contact_id' => Contact::factory(),
            'category' => fake()->randomElement(['newsletter', 'promotional', 'transactional']),
            'subscribed' => true,
        ];
    }
}
