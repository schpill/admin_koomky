<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Client;
use App\Models\User;
use App\Services\ReferenceGeneratorService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

final class ImportClientJob implements ShouldQueue
{
    use Batchable, Queueable;

    public string $queue = 'imports';

    public function __construct(
        public User $user,
        public array $data
    ) {}

    public function handle(ReferenceGeneratorService $referenceGenerator): void
    {
        DB::transaction(function () use ($referenceGenerator) {
            $client = Client::create([
                'user_id' => $this->user->id,
                'reference' => $referenceGenerator->generateClientReference($this->user),
                'company_name' => $this->data['company'] ?? null,
                'first_name' => $this->data['first_name'] ?? null,
                'last_name' => $this->data['last_name'] ?? null,
                'email' => $this->data['email'] ?? null,
                'phone' => $this->data['phone'] ?? null,
                'vat_number' => $this->data['vat_number'] ?? null,
                'website' => $this->data['website'] ?? null,
                'address' => $this->data['address'] ?? null,
                'city' => $this->data['city'] ?? null,
                'postal_code' => $this->data['postal_code'] ?? null,
                'country' => $this->data['country'] ?? null,
                'notes' => $this->data['notes'] ?? null,
            ]);

            // Log import activity
            $client->activities()->create([
                'user_id' => $this->user->id,
                'type' => 'system',
                'description' => 'Client imported via CSV',
            ]);
        });
    }
}
