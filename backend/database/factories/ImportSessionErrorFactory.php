<?php

namespace Database\Factories;

use App\Models\ImportSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportSessionErrorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'session_id' => ImportSession::factory(),
            'row_number' => 2,
            'raw_data' => ['name' => 'Invalid Row'],
            'error_message' => 'Invalid row',
        ];
    }
}
