<?php

use App\Models\Client;
use App\Models\Contact;
use App\Models\Segment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeSegmentWithCityFilter(User $user, string $city): Segment
{
    return Segment::factory()->create([
        'user_id' => $user->id,
        'filters' => [
            'group_boolean' => 'and',
            'criteria_boolean' => 'or',
            'groups' => [
                [
                    'criteria' => [
                        ['type' => 'location', 'field' => 'city', 'operator' => 'equals', 'value' => $city],
                    ],
                ],
            ],
        ],
    ]);
}

test('preview returns matching contacts with count and pagination and excludes unsubscribed', function () {
    $user = User::factory()->create();

    $parisClientA = Client::factory()->create(['user_id' => $user->id, 'city' => 'Paris']);
    $parisClientB = Client::factory()->create(['user_id' => $user->id, 'city' => 'Paris']);
    $parisClientUnsubscribed = Client::factory()->create(['user_id' => $user->id, 'city' => 'Paris']);
    $lyonClient = Client::factory()->create(['user_id' => $user->id, 'city' => 'Lyon']);

    Contact::factory()->create(['client_id' => $parisClientA->id, 'email' => 'a@city.test']);
    Contact::factory()->create(['client_id' => $parisClientB->id, 'email' => 'b@city.test']);
    Contact::factory()->create([
        'client_id' => $parisClientUnsubscribed->id,
        'email' => 'out@city.test',
        'email_unsubscribed_at' => now(),
    ]);
    Contact::factory()->create(['client_id' => $lyonClient->id, 'email' => 'lyon@city.test']);

    $segment = makeSegmentWithCityFilter($user, 'Paris');

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/segments/'.$segment->id.'/preview?per_page=1&page=1');

    $response->assertStatus(200)
        ->assertJsonPath('data.total_matching', 2)
        ->assertJsonPath('data.contacts.current_page', 1)
        ->assertJsonCount(1, 'data.contacts.data');
});

test('preview resolves segment dynamically for every request', function () {
    $user = User::factory()->create();

    $client = Client::factory()->create(['user_id' => $user->id, 'city' => 'Lyon']);
    Contact::factory()->create(['client_id' => $client->id, 'email' => 'contact@dynamic.test']);

    $segment = makeSegmentWithCityFilter($user, 'Paris');

    $firstResponse = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/segments/'.$segment->id.'/preview');

    $firstResponse->assertStatus(200)
        ->assertJsonPath('data.total_matching', 0);

    $client->update(['city' => 'Paris']);

    $secondResponse = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/segments/'.$segment->id.'/preview');

    $secondResponse->assertStatus(200)
        ->assertJsonPath('data.total_matching', 1);
});
