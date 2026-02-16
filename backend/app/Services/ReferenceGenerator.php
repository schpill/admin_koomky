<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReferenceGenerator
{
    public static function generate(string $table, string $prefix): string
    {
        $year = now()->format('Y');
        $counterKey = "{$table}:{$prefix}:{$year}";

        return DB::transaction(function () use ($table, $prefix, $year, $counterKey): string {
            $counter = DB::table('reference_counters')
                ->where('counter_key', $counterKey)
                ->lockForUpdate()
                ->first();

            $existingMax = self::existingMaxNumber($table, $prefix, $year);
            $lastNumber = max((int) ($counter->last_number ?? 0), $existingMax);
            $nextNumber = $lastNumber + 1;
            $now = now();

            if ($counter) {
                DB::table('reference_counters')
                    ->where('counter_key', $counterKey)
                    ->update([
                        'last_number' => $nextNumber,
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('reference_counters')->insert([
                    'counter_key' => $counterKey,
                    'last_number' => $nextNumber,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return sprintf('%s-%s-%04d', $prefix, $year, $nextNumber);
        });
    }

    private static function existingMaxNumber(string $table, string $prefix, string $year): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $referenceColumn = self::referenceColumn($table);
        if (! Schema::hasColumn($table, $referenceColumn)) {
            return 0;
        }

        /** @var string|null $latestReference */
        $latestReference = DB::table($table)
            ->where($referenceColumn, 'like', "{$prefix}-{$year}-%")
            ->orderBy($referenceColumn, 'desc')
            ->value($referenceColumn);

        if (! is_string($latestReference) || strlen($latestReference) < 4) {
            return 0;
        }

        return (int) substr($latestReference, -4);
    }

    private static function referenceColumn(string $table): string
    {
        return match ($table) {
            'invoices' => 'number',
            'quotes' => 'number',
            'credit_notes' => 'number',
            default => 'reference',
        };
    }
}
