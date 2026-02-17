<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CampaignAttachment>
 */
class CampaignAttachmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'filename' => 'attachment-'.$this->faker->numberBetween(1, 999).'.pdf',
            'path' => 'campaign-attachments/file-'.$this->faker->numberBetween(1, 999).'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => $this->faker->numberBetween(1024, 512000),
        ];
    }
}
