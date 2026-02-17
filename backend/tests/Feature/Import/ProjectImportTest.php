<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

test('project csv import creates projects and reports row errors', function () {
    $user = User::factory()->create();
    $client = Client::factory()->for($user)->create(['reference' => 'CLI-2026-0001']);

    $csv = implode("\n", [
        'name,client_reference,billing_type,hourly_rate,start_date,deadline,status',
        'Migration project,CLI-2026-0001,hourly,125,2026-02-01,2026-03-01,in_progress',
        'Broken row,UNKNOWN,hourly,90,2026-02-01,2026-03-01,draft',
    ]);

    $file = UploadedFile::fake()->createWithContent('projects.csv', $csv);

    $response = $this->actingAs($user, 'sanctum')
        ->post('/api/v1/import/projects', [
            'file' => $file,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.imported', 1)
        ->assertJsonPath('data.errors.0.row', 3);

    expect(Project::query()->where('user_id', $user->id)->count())->toBe(1);
    expect(Project::query()->first()?->name)->toBe('Migration project');
});
