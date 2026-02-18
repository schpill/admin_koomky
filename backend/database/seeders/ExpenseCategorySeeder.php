<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'Travel', 'color' => '#3B82F6', 'icon' => 'plane'],
            ['name' => 'Software', 'color' => '#8B5CF6', 'icon' => 'laptop'],
            ['name' => 'Hardware', 'color' => '#F59E0B', 'icon' => 'monitor'],
            ['name' => 'Office Supplies', 'color' => '#10B981', 'icon' => 'briefcase'],
            ['name' => 'Meals', 'color' => '#EF4444', 'icon' => 'utensils'],
            ['name' => 'Professional Services', 'color' => '#6366F1', 'icon' => 'users'],
            ['name' => 'Marketing', 'color' => '#EC4899', 'icon' => 'megaphone'],
            ['name' => 'Training', 'color' => '#14B8A6', 'icon' => 'book'],
            ['name' => 'Subscriptions', 'color' => '#0EA5E9', 'icon' => 'repeat'],
            ['name' => 'Other', 'color' => '#6B7280', 'icon' => 'wallet'],
        ];

        User::query()->each(function (User $user) use ($defaults): void {
            foreach ($defaults as $category) {
                ExpenseCategory::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'name' => $category['name'],
                    ],
                    [
                        'color' => $category['color'],
                        'icon' => $category['icon'],
                        'is_default' => true,
                    ]
                );
            }
        });
    }
}
