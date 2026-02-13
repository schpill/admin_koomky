<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Str;

final readonly class ReferenceGeneratorService
{
    /**
     * Generate a UUID for entity IDs.
     */
    public function generateUuid(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Generate a unique client reference.
     * Format: CLI-YYYYMMDD-XXXX where XXXX is a sequential number.
     */
    public function generateClientReference(User $user): string
    {
        $date = now()->format('Ymd');

        // Get the count of clients created today for this user
        $count = Client::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->count();

        $sequence = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        return "CLI-{$date}-{$sequence}";
    }

    /**
     * Generate a project reference.
     * Format: PRJ-YYYYMMDD-XXXX
     */
    public function generateProjectReference(User $user): string
    {
        $date = now()->format('Ymd');

        // Get the count of projects created today for this user
        $count = \App\Models\Project::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->count();

        $sequence = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        return "PRJ-{$date}-{$sequence}";
    }

    /**
     * Generate an invoice reference.
     * Format: INV-YYYYMM-XXXX
     */
    public function generateInvoiceReference(User $user): string
    {
        $date = now()->format('Ym');

        // Get the count of invoices created this month for this user
        $count = \App\Models\Invoice::where('user_id', $user->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $sequence = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        return "INV-{$date}-{$sequence}";
    }

    /**
     * Generate a quote reference.
     * Format: QUO-YYYYMM-XXXX
     */
    public function generateQuoteReference(User $user): string
    {
        $date = now()->format('Ym');

        // Get the count of quotes created this month for this user
        $count = \App\Models\Quote::where('user_id', $user->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $sequence = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

        return "QUO-{$date}-{$sequence}";
    }
}
