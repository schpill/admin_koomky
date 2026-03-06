<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_steps', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->enum('type', ['send_email', 'wait', 'condition', 'update_score', 'add_tag', 'remove_tag', 'enroll_drip', 'update_field', 'end']);
            $table->json('config');
            $table->uuid('next_step_id')->nullable();
            $table->uuid('else_step_id')->nullable();
            $table->float('position_x')->default(0);
            $table->float('position_y')->default(0);
            $table->timestamps();

            $table->index(['workflow_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};
