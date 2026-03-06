<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_enrollments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->foreignUuid('contact_id')->constrained()->cascadeOnDelete();
            $table->uuid('current_step_id')->nullable();
            $table->enum('status', ['active', 'completed', 'paused', 'cancelled', 'failed'])->default('active');
            $table->timestamp('enrolled_at');
            $table->timestamp('last_processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_processed_at']);
            $table->index(['workflow_id', 'contact_id']);
        });

        DB::statement("CREATE UNIQUE INDEX workflow_enrollments_active_unique ON workflow_enrollments (workflow_id, contact_id) WHERE status = 'active'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS workflow_enrollments_active_unique');
        Schema::dropIfExists('workflow_enrollments');
    }
};
