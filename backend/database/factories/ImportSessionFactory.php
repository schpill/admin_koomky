<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'filename' => 'imports/test.csv',
            'original_filename' => 'test.csv',
            'status' => 'pending',
            'total_rows' => 0,
            'processed_rows' => 0,
            'success_rows' => 0,
            'error_rows' => 0,
            'column_mapping' => null,
            'default_tags' => null,
            'options' => null,
            'error_summary' => null,
            'completed_at' => null,
        ];
    }
}
