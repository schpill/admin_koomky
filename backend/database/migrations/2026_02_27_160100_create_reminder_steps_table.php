<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_steps', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('sequence_id')->constrained('reminder_sequences')->cascadeOnDelete();
            $table->integer('step_number');
            $table->integer('delay_days');
            $table->string('subject', 255);
            $table->text('body');
            $table->timestamps();

            $table->index(['sequence_id', 'step_number']);
            $table->unique(['sequence_id', 'step_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_steps');
    }
};
