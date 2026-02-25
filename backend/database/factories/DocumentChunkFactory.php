<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentChunkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'user_id' => User::factory(),
            'chunk_index' => 0,
            'content' => 'chunk content',
            'embedding' => [0.1, 0.2, 0.3],
            'token_count' => 10,
            'created_at' => now(),
        ];
    }
}
