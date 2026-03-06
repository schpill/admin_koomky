<?php

use App\Services\DeliverabilityScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('deliverability analysis flags spammy subject and missing unsubscribe link', function () {
    $analysis = app(DeliverabilityScoreService::class)->analyze(
        'FREE URGENT OFFER!!!',
        '<html><body><img src="hero.jpg"></body></html>'
    );

    expect($analysis['score'])->toBeLessThan(100)
        ->and($analysis['issues'])->not->toBeEmpty()
        ->and(collect($analysis['issues'])->pluck('severity')->all())->toContain('error', 'warning');
});
