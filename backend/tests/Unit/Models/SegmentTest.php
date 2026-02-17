<?php

use App\Models\Segment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('segment factory creates valid model with json filters cast', function () {
    $segment = Segment::factory()->create([
        'filters' => [
            'group_boolean' => 'and',
            'criteria_boolean' => 'or',
            'groups' => [
                [
                    'criteria' => [
                        ['type' => 'tag', 'operator' => 'equals', 'value' => 'VIP'],
                    ],
                ],
            ],
        ],
    ]);

    expect($segment->id)->toBeString();
    expect($segment->filters)->toBeArray();
    expect($segment->is_dynamic)->toBeTrue();
});

test('segment belongs to a user', function () {
    $user = User::factory()->create();
    $segment = Segment::factory()->create(['user_id' => $user->id]);

    expect($segment->user->id)->toBe($user->id);
});
