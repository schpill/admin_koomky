<?php

use App\Models\Client;
use App\Models\Contact;
use App\Models\User;
use App\Services\PersonalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('render rewrites trackable links and preserves mailto tel and already tracked links', function () {
    $user = User::factory()->create();
    $client = Client::query()->create([
        'user_id' => $user->id,
        'reference' => 'CLI-2026-1001',
        'name' => 'Acme',
        'email' => 'client@example.test',
        'phone' => '+33102030405',
        'address' => '1 rue Example',
        'city' => 'Paris',
        'zip_code' => '75001',
        'country' => 'France',
        'industry' => 'Wedding Planner',
        'department' => '75',
        'status' => 'active',
    ]);
    $contact = Contact::factory()->for($client)->create([
        'first_name' => 'Alice',
    ]);

    $service = app(PersonalizationService::class);

    $html = <<<'HTML'
<p>Hello {{first_name}}</p>
<a href="https://example.com/offer">Offer</a>
<a href="mailto:alice@example.com">Email</a>
<a href="tel:+33102030405">Phone</a>
<a href="/t/click/already-tracked?url=https%3A%2F%2Fexample.com%2Fdone">Tracked</a>
HTML;

    $rendered = $service->render($html, $contact, 'recipient-token');

    expect($rendered)
        ->toContain('Hello Alice')
        ->toContain('href="'.url('/t/click/recipient-token').'?url='.urlencode('https://example.com/offer').'"')
        ->toContain('href="mailto:alice@example.com"')
        ->toContain('href="tel:+33102030405"')
        ->toContain('href="/t/click/already-tracked?url=https%3A%2F%2Fexample.com%2Fdone"');
});
