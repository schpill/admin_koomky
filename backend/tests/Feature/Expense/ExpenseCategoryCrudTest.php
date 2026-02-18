<?php

use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can create list update and delete custom expense categories', function () {
    $user = User::factory()->create();

    $create = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/expense-categories', [
            'name' => 'Travel',
            'color' => '#123456',
            'icon' => 'plane',
        ]);

    $create
        ->assertStatus(201)
        ->assertJsonPath('data.name', 'Travel');

    $categoryId = (string) $create->json('data.id');

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/expense-categories')
        ->assertStatus(200)
        ->assertJsonPath('data.0.id', $categoryId);

    $this->actingAs($user, 'sanctum')
        ->putJson('/api/v1/expense-categories/'.$categoryId, [
            'name' => 'Transport',
            'color' => '#654321',
            'icon' => 'car',
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.name', 'Transport');

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/expense-categories/'.$categoryId)
        ->assertStatus(200);

    $this->assertDatabaseMissing('expense_categories', ['id' => $categoryId]);
});

test('default expense categories cannot be deleted', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create([
        'user_id' => $user->id,
        'is_default' => true,
    ]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/expense-categories/'.$category->id)
        ->assertStatus(422);
});

test('expense category validation errors are returned', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/expense-categories', [
            'name' => '',
        ])
        ->assertStatus(422);
});
