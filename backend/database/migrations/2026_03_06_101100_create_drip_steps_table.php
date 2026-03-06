<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drip_steps', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('sequence_id')->constrained('drip_sequences')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->unsignedSmallInteger('delay_hours')->default(0);
            $table->enum('condition', ['none', 'if_opened', 'if_clicked', 'if_not_opened'])->default('none');
            $table->string('subject');
            $table->text('content');
            $table->foreignUuid('template_id')->nullable()->constrained('campaign_templates')->nullOnDelete();
            $table->timestamps();

            $table->unique(['sequence_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drip_steps');
    }
};
