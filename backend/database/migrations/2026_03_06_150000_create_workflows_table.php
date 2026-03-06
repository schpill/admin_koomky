<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('trigger_type', ['email_opened', 'email_clicked', 'score_threshold', 'contact_created', 'contact_updated', 'segment_entered', 'manual']);
            $table->json('trigger_config')->nullable();
            $table->enum('status', ['draft', 'active', 'paused', 'archived'])->default('draft');
            $table->uuid('entry_step_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['trigger_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
