<?php

use App\Services\FileParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeSimpleXlsx(string $path): void
{
    $shared = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="4" uniqueCount="4"><si><t>Name</t></si><si><t>Email</t></si><si><t>Acme</t></si><si><t>acme@example.com</t></si></sst>';
    $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c></row><row r="2"><c r="A2" t="s"><v>2</v></c><c r="B2" t="s"><v>3</v></c></row></sheetData></worksheet>';

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('xl/sharedStrings.xml', $shared);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
    $zip->close();
}

test('parses utf8 csv and semicolon csv and xlsx', function () {
    $service = app(FileParserService::class);

    $csvPath = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($csvPath, "Name,Email\nAcme,acme@example.com\n");
    $parsedCsv = $service->parse($csvPath, 'csv');

    expect($parsedCsv['headers'])->toBe(['Name', 'Email']);
    expect($parsedCsv['rows'][0]['Name'])->toBe('Acme');

    $csvSemiPath = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($csvSemiPath, "Name;Email\nAcme;acme@example.com\n");
    $parsedSemi = $service->parse($csvSemiPath, 'csv');
    expect($parsedSemi['rows'][0]['Email'])->toBe('acme@example.com');

    $xlsxPath = tempnam(sys_get_temp_dir(), 'xlsx_');
    makeSimpleXlsx($xlsxPath);
    $parsedXlsx = $service->parse($xlsxPath, 'xlsx');

    expect($parsedXlsx['headers'])->toBe(['Name', 'Email']);
    expect($parsedXlsx['rows'][0]['Name'])->toBe('Acme');
});

test('throws on empty file and on more than ten thousand rows', function () {
    $service = app(FileParserService::class);

    $emptyPath = tempnam(sys_get_temp_dir(), 'csv_');
    file_put_contents($emptyPath, '');
    expect(fn () => $service->parse($emptyPath, 'csv'))->toThrow(RuntimeException::class);

    $tooLargePath = tempnam(sys_get_temp_dir(), 'csv_');
    $content = "Name,Email\n";
    for ($i = 0; $i < 10001; $i++) {
        $content .= "Name{$i},n{$i}@mail.test\n";
    }
    file_put_contents($tooLargePath, $content);

    expect(fn () => $service->parse($tooLargePath, 'csv'))->toThrow(RuntimeException::class);
});

test('normalizes latin1 csv into utf8', function () {
    $service = app(FileParserService::class);

    $path = tempnam(sys_get_temp_dir(), 'csv_');
    $content = mb_convert_encoding("Nom,Email\nRené,rene@example.com\n", 'ISO-8859-1', 'UTF-8');
    file_put_contents($path, $content);

    $parsed = $service->parse($path, 'csv');

    expect($parsed['rows'][0]['Nom'])->toBe('René');
});
