<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_link_clicks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('recipient_id')->constrained('campaign_recipients')->cascadeOnDelete();
            $table->foreignUuid('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url', 2048);
            $table->timestamp('clicked_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('recipient_id');
            $table->index(['campaign_id', 'url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_link_clicks');
    }
};
