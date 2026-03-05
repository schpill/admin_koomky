<?php

use App\Services\ColumnDetectorService;
use Tests\TestCase;

uses(TestCase::class);

it('detects expected mappings from aliases and ignores unknown columns', function () {
    $service = app(ColumnDetectorService::class);

    $mapping = $service->detect([
        'Nom',
        'Email',
        'Téléphone',
        'Département',
        "Secteur d'activité",
        'Unknown column',
    ]);

    expect($mapping['Nom'])->toBe('name');
    expect($mapping['Email'])->toBe('email');
    expect($mapping['Téléphone'])->toBe('phone');
    expect($mapping['Département'])->toBe('department');
    expect($mapping["Secteur d'activité"])->toBe('industry');
    expect($mapping['Unknown column'])->toBeNull();
});

it('matches aliases case-insensitively', function () {
    $service = app(ColumnDetectorService::class);

    $mapping = $service->detect(['EMAIL', 'FIRST NAME', 'LastName']);

    expect($mapping['EMAIL'])->toBe('email');
    expect($mapping['FIRST NAME'])->toBe('contact.first_name');
    expect($mapping['LastName'])->toBe('contact.last_name');
});
