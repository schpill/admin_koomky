<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Segment>
 */
class SegmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'filters' => [
                'group_boolean' => 'and',
                'criteria_boolean' => 'or',
                'groups' => [
                    [
                        'criteria' => [
                            [
                                'type' => 'location',
                                'field' => 'city',
                                'operator' => 'equals',
                                'value' => 'Paris',
                            ],
                        ],
                    ],
                ],
            ],
            'is_dynamic' => true,
            'contact_count' => 0,
        ];
    }
}
