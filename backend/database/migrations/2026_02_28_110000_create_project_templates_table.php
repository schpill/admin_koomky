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
        Schema::create('project_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('billing_type', ['hourly', 'fixed'])->nullable();
            $table->decimal('default_hourly_rate', 10, 2)->nullable();
            $table->string('default_currency', 3)->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_templates');
    }
};