<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

test('expenses can be imported from csv', function () {
    $user = User::factory()->create();
    ExpenseCategory::factory()->create([
        'user_id' => $user->id,
        'name' => 'Travel',
    ]);

    $csv = implode("\n", [
        'category_name,description,amount,currency,date,is_billable,status',
        'Travel,Taxi ride,25,EUR,'.now()->toDateString().',1,approved',
    ]);

    $file = UploadedFile::fake()->createWithContent('expenses.csv', $csv);

    $response = $this->actingAs($user, 'sanctum')
        ->post('/api/v1/import/expenses', [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

    $response
        ->assertStatus(200)
        ->assertJsonPath('data.imported', 1);

    $this->assertDatabaseHas('expenses', [
        'user_id' => $user->id,
        'description' => 'Taxi ride',
    ]);
});

test('gdpr export includes expenses payload', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create([
        'user_id' => $user->id,
    ]);
    Expense::factory()->create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
        'description' => 'Laptop stand',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->get('/api/v1/export/full');
    $response->assertStatus(200);

    $archivePath = tempnam(sys_get_temp_dir(), 'koomky-expenses-export-');
    if ($archivePath === false) {
        throw new \RuntimeException('Unable to create temporary file');
    }

    file_put_contents($archivePath, $response->streamedContent());

    $zip = new \ZipArchive;
    $opened = $zip->open($archivePath);

    expect($opened)->toBe(true);

    $json = $zip->getFromName('export.json');
    $zip->close();

    @unlink($archivePath);

    expect($json)->toBeString();

    /** @var array<string, mixed> $payload */
    $payload = json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);

    expect($payload)->toHaveKey('expenses');
    expect($payload['expenses'])->toBeArray();
    expect($payload['expenses'])->not->toBeEmpty();
});
