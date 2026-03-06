<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppressed_emails', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->enum('reason', ['unsubscribed', 'hard_bounce', 'manual']);
            $table->foreignUuid('source_campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->timestamp('suppressed_at');
            $table->timestamps();

            $table->unique(['user_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppressed_emails');
    }
};
