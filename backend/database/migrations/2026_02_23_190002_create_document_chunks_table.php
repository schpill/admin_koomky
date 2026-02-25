<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_chunks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->text('content');
            $table->text('embedding')->nullable();
            $table->unsignedInteger('token_count');
            $table->timestamp('created_at')->useCurrent();

            $table->index('document_id');
            $table->index('user_id');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            try {
                $hasVector = (bool) (DB::selectOne(
                    "SELECT EXISTS (SELECT 1 FROM pg_extension WHERE extname = 'vector') AS installed"
                )->installed ?? false);

                if ($hasVector) {
                    DB::statement('ALTER TABLE document_chunks ALTER COLUMN embedding TYPE vector(768) USING embedding::vector');
                    DB::statement('CREATE INDEX document_chunks_embedding_hnsw ON document_chunks USING hnsw (embedding vector_cosine_ops)');
                }
            } catch (QueryException $exception) {
                Log::warning('pgvector_index_creation_skipped', [
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
