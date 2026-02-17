<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'position' => $this->faker->jobTitle(),
            'is_primary' => false,
            'email_unsubscribed_at' => null,
            'sms_opted_out_at' => null,
            'email_consent' => false,
            'email_consent_date' => null,
            'sms_consent' => false,
            'sms_consent_date' => null,
        ];
    }
}
