<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReferenceGenerator
{
    public static function generate(string $table, string $prefix): string
    {
        $year = date('Y');

        $lastReference = DB::table($table)
            ->where('reference', 'like', "{$prefix}-{$year}-%")
            ->orderBy('reference', 'desc')
            ->first();

        if (! $lastReference) {
            $number = 1;
        } else {
            $lastNumber = (int) substr($lastReference->reference, -4);
            $number = $lastNumber + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $number);
    }
}
