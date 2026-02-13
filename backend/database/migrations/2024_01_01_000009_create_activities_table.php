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
        Schema::create('activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('client_id')->nullable();
            $table->string('subject_type')->nullable();
            $table->uuid('subject_id')->nullable();
            $table->string('type');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('client_id');
            $table->index(['subject_type', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
