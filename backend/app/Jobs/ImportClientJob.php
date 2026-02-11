<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Client;
use App\Models\User;
use App\Services\ReferenceGeneratorService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

final readonly class ImportClientJob implements ShouldQueue
{
    use Batchable;

    public function __construct(
        public User $user,
        public array $data
    ) {
        $this->onQueue('imports');
    }

    public function handle(ReferenceGeneratorService $referenceGenerator): void
    {
        DB::transaction(function () use ($referenceGenerator) {
            $client = Client::create([
                'id' => $referenceGenerator->generateUuid(),
                'user_id' => $this->user->id,
                'reference' => $referenceGenerator->generateClientReference($this->user),
                'name' => $this->data['name'],
                'email' => $this->data['email'] ?? null,
                'phone' => $this->data['phone'] ?? null,
                'company' => $this->data['company'] ?? null,
                'vat_number' => $this->data['vat_number'] ?? null,
                'website' => $this->data['website'] ?? null,
                'billing_address' => $this->data['billing_address'] ?? null,
                'notes' => $this->data['notes'] ?? null,
                'status' => 'active',
            ]);

            // Log import activity
            $client->activities()->create([
                'id' => $referenceGenerator->generateUuid(),
                'user_id' => $this->user->id,
                'action' => 'imported',
                'description' => "Client imported via CSV",
            ]);
        });
    }
}
