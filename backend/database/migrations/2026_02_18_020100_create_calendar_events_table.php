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
        Schema::create('calendar_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('calendar_connection_id')->nullable()->constrained('calendar_connections')->nullOnDelete();
            $table->string('external_id', 500)->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('start_at')->index();
            $table->timestamp('end_at');
            $table->boolean('all_day')->default(false);
            $table->string('location', 500)->nullable();
            $table->enum('type', ['meeting', 'deadline', 'reminder', 'task', 'custom'])->default('custom')->index();
            $table->string('eventable_type')->nullable();
            $table->uuid('eventable_id')->nullable();
            $table->string('recurrence_rule', 500)->nullable();
            $table->enum('sync_status', ['local', 'synced', 'conflict'])->default('local')->index();
            $table->timestamp('external_updated_at')->nullable();
            $table->timestamps();

            $table->index(['eventable_type', 'eventable_id']);
            $table->unique(['calendar_connection_id', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
