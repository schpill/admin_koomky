<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drip_sequences', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('trigger_event', ['campaign_sent', 'contact_created', 'manual']);
            $table->foreignUuid('trigger_campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->enum('status', ['active', 'paused', 'archived'])->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drip_sequences');
    }
};
