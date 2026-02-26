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
        Schema::create('product_campaign_generation_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('model', 100);
            $table->unsignedInteger('tokens_used')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->boolean('success');
            $table->text('error_message')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index(['user_id', 'generated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_campaign_generation_logs');
    }
};
