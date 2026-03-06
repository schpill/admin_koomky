<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_warmup_plans', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status', 20)->default('active');
            $table->integer('daily_volume_start');
            $table->integer('daily_volume_max');
            $table->unsignedTinyInteger('increment_percent')->default(30);
            $table->unsignedSmallInteger('current_day')->default(0);
            $table->integer('current_daily_limit');
            $table->timestamp('started_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_warmup_plans');
    }
};
