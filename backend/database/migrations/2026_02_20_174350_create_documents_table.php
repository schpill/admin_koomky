<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('title');
            $table->string('original_filename', 500);
            $table->string('storage_path', 1000);
            $table->string('storage_disk', 50)->default('local');
            $table->string('mime_type', 150);
            $table->enum('document_type', [
                'pdf', 'spreadsheet', 'document', 'text', 'script', 'image', 'archive', 'presentation', 'other'
            ])->default('other');
            $table->string('script_language', 30)->nullable();
            $table->unsignedBigInteger('file_size');
            $table->unsignedTinyInteger('version')->default(1);
            $table->jsonb('tags')->default('[]');
            $table->timestamp('last_sent_at')->nullable();
            $table->string('last_sent_to', 500)->nullable();
            $table->timestamps();

            $table->index('document_type');
            $table->index(['user_id', 'document_type']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
