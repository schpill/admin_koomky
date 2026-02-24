<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            try {
                DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
            } catch (QueryException $exception) {
                Log::warning('pgvector_extension_unavailable', [
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // intentionally irreversible
    }
};
