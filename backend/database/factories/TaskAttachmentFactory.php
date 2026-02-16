<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\TaskAttachment>
 */
class TaskAttachmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'filename' => $this->faker->lexify('file-????.txt'),
            'path' => 'tasks/'.$this->faker->uuid().'/'.$this->faker->lexify('file-????.txt'),
            'mime_type' => 'text/plain',
            'size_bytes' => $this->faker->numberBetween(1024, 500000),
        ];
    }
}
