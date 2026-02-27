<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_reminder_schedules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('sequence_id')->nullable()->constrained('reminder_sequences')->nullOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->boolean('is_paused')->default(false);
            $table->foreignUuid('next_reminder_step_id')->nullable()->constrained('reminder_steps')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_reminder_schedules');
    }
};
