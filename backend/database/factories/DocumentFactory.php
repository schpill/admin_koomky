<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalFilename = $this->faker->word().'.'.$this->faker->fileExtension();
        $documentType = $this->faker->randomElement(DocumentType::cases());
        $scriptLanguage = null;
        if ($documentType === DocumentType::SCRIPT) {
            $scriptLanguage = $this->faker->randomElement(['python', 'php', 'javascript', 'typescript', 'html', 'css', 'shell', 'ruby', 'go']);
        }

        return [
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'title' => $this->faker->sentence(3),
            'original_filename' => $originalFilename,
            'storage_path' => 'documents/'.$this->faker->uuid().'/'.$originalFilename,
            'storage_disk' => 'local',
            'mime_type' => $this->faker->mimeType(),
            'document_type' => $documentType,
            'script_language' => $scriptLanguage,
            'file_size' => $this->faker->numberBetween(1000, 10000000),
            'version' => 1,
            'tags' => $this->faker->words(3),
            'last_sent_at' => null,
            'last_sent_to' => null,
        ];
    }
}
