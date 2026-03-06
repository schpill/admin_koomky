<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drip_enrollments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('sequence_id')->constrained('drip_sequences')->cascadeOnDelete();
            $table->foreignUuid('contact_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('current_step_position')->default(0);
            $table->enum('status', ['active', 'completed', 'paused', 'cancelled', 'failed'])->default('active');
            $table->timestamp('enrolled_at');
            $table->timestamp('last_processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['sequence_id', 'contact_id']);
            $table->index(['status', 'last_processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drip_enrollments');
    }
};
