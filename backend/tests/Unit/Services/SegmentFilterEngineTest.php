<?php

use App\Models\Activity;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use App\Services\SegmentFilterEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->engine = app(SegmentFilterEngine::class);

    $this->makeContact = function (User $user, array $clientAttributes = [], array $contactAttributes = []): Contact {
        $client = Client::factory()->create(array_merge([
            'user_id' => $user->id,
        ], $clientAttributes));

        return Contact::factory()->create(array_merge([
            'client_id' => $client->id,
        ], $contactAttributes));
    };
});

test('filters contacts by tag criterion', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $user->id, 'name' => 'VIP']);

    $matching = ($this->makeContact)($user);
    $nonMatching = ($this->makeContact)($user);

    $matching->client->tags()->attach($tag->id);

    $filters = [
        'groups' => [
            [
                'criteria' => [
                    ['type' => 'tag', 'operator' => 'equals', 'value' => 'VIP'],
                ],
            ],
        ],
    ];

    $results = $this->engine->apply($user, $filters)->pluck('id')->all();

    expect($results)->toContain($matching->id);
    expect($results)->not->toContain($nonMatching->id);
});

test('filters contacts by last interaction criterion', function () {
    $user = User::factory()->create();

    $oldContact = ($this->makeContact)($user);
    $recentContact = ($this->makeContact)($user);

    $oldActivity = Activity::query()->create([
        'user_id' => $user->id,
        'subject_id' => $oldContact->client_id,
        'subject_type' => Client::class,
        'description' => 'Old interaction',
    ]);
    $oldActivity->forceFill([
        'created_at' => now()->subMonths(4),
        'updated_at' => now()->subMonths(4),
    ])->save();

    $recentActivity = Activity::query()->create([
        'user_id' => $user->id,
        'subject_id' => $recentContact->client_id,
        'subject_type' => Client::class,
        'description' => 'Recent interaction',
    ]);
    $recentActivity->forceFill([
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ])->save();

    $filters = [
        'groups' => [
            [
                'criteria' => [
                    ['type' => 'last_interaction', 'operator' => 'older_than_months', 'value' => 3],
                ],
            ],
        ],
    ];

    $results = $this->engine->apply($user, $filters)->pluck('id')->all();

    expect($results)->toContain($oldContact->id);
    expect($results)->not->toContain($recentContact->id);
});

test('filters contacts by project status criterion', function () {
    $user = User::factory()->create();

    $inProgressContact = ($this->makeContact)($user);
    $draftContact = ($this->makeContact)($user);

    Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $inProgressContact->client_id,
        'status' => 'in_progress',
    ]);

    Project::factory()->create([
        'user_id' => $user->id,
        'client_id' => $draftContact->client_id,
        'status' => 'draft',
    ]);

    $filters = [
        'groups' => [
            [
                'criteria' => [
                    ['type' => 'project_status', 'operator' => 'equals', 'value' => 'in_progress'],
                ],
            ],
        ],
    ];

    $results = $this->engine->apply($user, $filters)->pluck('id')->all();

    expect($results)->toContain($inProgressContact->id);
    expect($results)->not->toContain($draftContact->id);
});

test('filters contacts by revenue criterion', function () {
    $user = User::factory()->create();

    $highRevenueContact = ($this->makeContact)($user);
    $lowRevenueContact = ($this->makeContact)($user);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $highRevenueContact->client_id,
        'total' => 2500,
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $lowRevenueContact->client_id,
        'total' => 500,
    ]);

    $filters = [
        'groups' => [
            [
                'criteria' => [
                    ['type' => 'revenue', 'operator' => 'gt', 'value' => 1000],
                ],
            ],
        ],
    ];

    $results = $this->engine->apply($user, $filters)->pluck('id')->all();

    expect($results)->toContain($highRevenueContact->id);
    expect($results)->not->toContain($lowRevenueContact->id);
});

test('filters contacts by location criterion', function () {
    $user = User::factory()->create();

    $parisContact = ($this->makeContact)($user, ['city' => 'Paris', 'country' => 'France']);
    $londonContact = ($this->makeContact)($user, ['city' => 'London', 'country' => 'United Kingdom']);

    $filters = [
        'groups' => [
            [
                'criteria' => [
                    ['type' => 'location', 'field' => 'city', 'operator' => 'equals', 'value' => 'Paris'],
                ],
            ],
        ],
    ];

    $results = $this->engine->apply($user, $filters)->pluck('id')->all();

    expect($results)->toContain($parisContact->id);
    expect($results)->not->toContain($londonContact->id);
});

test('filters contacts by created_at criterion', function () {
    $user = User::factory()->create();

    $oldContact = ($this->makeContact)($user, [], ['created_at' => now()->subMonths(2), 'updated_at' => now()->subMonths(2)]);
    $newContact = ($this->makeContact)($user, [], ['created_at' => now()->subDays(5), 'updated_at' => now()->subDays(5)]);

    $filters = [
        'groups' => [
            [
                'criteria' => [
                    ['type' => 'created_at', 'operator' => 'before', 'value' => now()->subMonth()->toDateString()],
                ],
            ],
        ],
    ];

    $results = $this->engine->apply($user, $filters)->pluck('id')->all();

    expect($results)->toContain($oldContact->id);
    expect($results)->not->toContain($newContact->id);
});

test('filters contacts by custom field criterion', function () {
    $user = User::factory()->create();

    $hasEmail = ($this->makeContact)($user, [], ['email' => 'jane@example.com']);
    $noEmail = ($this->makeContact)($user, [], ['email' => null]);

    $filters = [
        'groups' => [
            [
                'criteria' => [
                    ['type' => 'custom_field', 'field' => 'email', 'operator' => 'exists'],
                ],
            ],
        ],
    ];

    $results = $this->engine->apply($user, $filters)->pluck('id')->all();

    expect($results)->toContain($hasEmail->id);
    expect($results)->not->toContain($noEmail->id);
});

test('supports and or combinators for groups and criteria', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $user->id, 'name' => 'VIP']);

    $bothMatch = ($this->makeContact)($user, ['city' => 'Paris']);
    $onlyTagMatch = ($this->makeContact)($user, ['city' => 'Lyon']);
    $onlyCityMatch = ($this->makeContact)($user, ['city' => 'Paris']);

    $bothMatch->client->tags()->attach($tag->id);
    $onlyTagMatch->client->tags()->attach($tag->id);

    $filters = [
        'groups' => [
            [
                'criteria' => [
                    ['type' => 'tag', 'operator' => 'equals', 'value' => 'VIP'],
                ],
            ],
            [
                'criteria' => [
                    ['type' => 'location', 'field' => 'city', 'operator' => 'equals', 'value' => 'Paris'],
                ],
            ],
        ],
    ];

    $andIds = $this->engine->apply($user, array_merge($filters, [
        'group_boolean' => 'and',
        'criteria_boolean' => 'or',
    ]))->pluck('id')->all();

    $orIds = $this->engine->apply($user, array_merge($filters, [
        'group_boolean' => 'or',
        'criteria_boolean' => 'or',
    ]))->pluck('id')->all();

    expect($andIds)->toContain($bothMatch->id);
    expect($andIds)->not->toContain($onlyTagMatch->id);
    expect($andIds)->not->toContain($onlyCityMatch->id);

    expect($orIds)->toContain($bothMatch->id);
    expect($orIds)->toContain($onlyTagMatch->id);
    expect($orIds)->toContain($onlyCityMatch->id);
});

test('empty filters return all contacts for the user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    ($this->makeContact)($user);
    ($this->makeContact)($user);
    ($this->makeContact)($otherUser);

    $results = $this->engine->apply($user, ['groups' => []])->get();

    expect($results)->toHaveCount(2);
});

test('invalid filter type throws exception', function () {
    $user = User::factory()->create();

    $filters = [
        'groups' => [
            [
                'criteria' => [
                    ['type' => 'invalid_type', 'operator' => 'equals', 'value' => 'x'],
                ],
            ],
        ],
    ];

    expect(fn () => $this->engine->apply($user, $filters)->get())
        ->toThrow(InvalidArgumentException::class);
});

test('supports list based filters format', function () {
    $user = User::factory()->create();
    $matching = ($this->makeContact)($user, ['country' => 'France']);
    ($this->makeContact)($user, ['country' => 'Spain']);

    $filters = [
        [
            'criteria' => [
                ['type' => 'location', 'field' => 'country', 'operator' => 'equals', 'value' => 'France'],
            ],
        ],
    ];

    $results = $this->engine->apply($user, $filters)->pluck('id')->all();

    expect($results)->toContain($matching->id);
});

test('supports tag in operator', function () {
    $user = User::factory()->create();
    $vipTag = Tag::factory()->create(['user_id' => $user->id, 'name' => 'VIP']);
    $prospectTag = Tag::factory()->create(['user_id' => $user->id, 'name' => 'Prospect']);

    $vipContact = ($this->makeContact)($user);
    $prospectContact = ($this->makeContact)($user);
    ($this->makeContact)($user);

    $vipContact->client->tags()->attach($vipTag->id);
    $prospectContact->client->tags()->attach($prospectTag->id);

    $filters = [
        'groups' => [
            [
                'criteria' => [
                    ['type' => 'tag', 'operator' => 'in', 'value' => ['VIP', 'Prospect']],
                ],
            ],
        ],
    ];

    $results = $this->engine->apply($user, $filters)->pluck('id')->all();

    expect($results)->toContain($vipContact->id);
    expect($results)->toContain($prospectContact->id);
});

test('supports location contains operator', function () {
    $user = User::factory()->create();

    $matching = ($this->makeContact)($user, ['country' => 'United Kingdom']);
    ($this->makeContact)($user, ['country' => 'France']);

    $filters = [
        'groups' => [
            [
                'criteria' => [
                    ['type' => 'location', 'field' => 'country', 'operator' => 'contains', 'value' => 'King'],
                ],
            ],
        ],
    ];

    $results = $this->engine->apply($user, $filters)->pluck('id')->all();

    expect($results)->toContain($matching->id);
});

test('supports custom field not_exists operator', function () {
    $user = User::factory()->create();

    ($this->makeContact)($user, [], ['phone' => '+33123456789']);
    $missingPhone = ($this->makeContact)($user, [], ['phone' => null]);

    $filters = [
        'groups' => [
            [
                'criteria' => [
                    ['type' => 'custom_field', 'field' => 'phone', 'operator' => 'not_exists'],
                ],
            ],
        ],
    ];

    $results = $this->engine->apply($user, $filters)->pluck('id')->all();

    expect($results)->toContain($missingPhone->id);
});

test('supports revenue equality operator', function () {
    $user = User::factory()->create();

    $exactRevenue = ($this->makeContact)($user);
    ($this->makeContact)($user);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'client_id' => $exactRevenue->client_id,
        'total' => 1000,
    ]);

    $filters = [
        'groups' => [
            [
                'criteria' => [
                    ['type' => 'revenue', 'operator' => 'eq', 'value' => 1000],
                ],
            ],
        ],
    ];

    $results = $this->engine->apply($user, $filters)->pluck('id')->all();

    expect($results)->toContain($exactRevenue->id);
});

test('supports last interaction before operator', function () {
    $user = User::factory()->create();

    $oldContact = ($this->makeContact)($user);
    ($this->makeContact)($user);

    $oldActivity = Activity::query()->create([
        'user_id' => $user->id,
        'subject_id' => $oldContact->client_id,
        'subject_type' => Client::class,
        'description' => 'Legacy interaction',
    ]);
    $oldActivity->forceFill([
        'created_at' => now()->subMonths(2),
        'updated_at' => now()->subMonths(2),
    ])->save();

    $filters = [
        'groups' => [
            [
                'criteria' => [
                    ['type' => 'last_interaction', 'operator' => 'before', 'value' => now()->subMonth()->toDateString()],
                ],
            ],
        ],
    ];

    $results = $this->engine->apply($user, $filters)->pluck('id')->all();

    expect($results)->toContain($oldContact->id);
});

test('throws exception when filters groups is not an array', function () {
    $user = User::factory()->create();

    expect(fn () => $this->engine->apply($user, ['groups' => 'invalid'])->get())
        ->toThrow(InvalidArgumentException::class);
});

test('throws exception when group boolean is invalid', function () {
    $user = User::factory()->create();

    expect(fn () => $this->engine->apply($user, [
        'groups' => [],
        'group_boolean' => 'xor',
    ])->get())->toThrow(InvalidArgumentException::class);
});

test('throws exception when criterion entry is not an array', function () {
    $user = User::factory()->create();

    expect(fn () => $this->engine->apply($user, [
        'groups' => [
            ['criteria' => ['invalid']],
        ],
    ])->get())->toThrow(InvalidArgumentException::class);
});
