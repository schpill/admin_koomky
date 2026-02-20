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
        Schema::create('leads', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->string('company_name', 255)->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->enum('source', ['manual', 'referral', 'website', 'campaign', 'other'])->default('manual');
            $table->enum('status', ['new', 'contacted', 'qualified', 'proposal_sent', 'negotiating', 'won', 'lost'])->default('new')->index();
            $table->decimal('estimated_value', 12, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->unsignedTinyInteger('probability')->nullable();
            $table->date('expected_close_date')->nullable()->index();
            $table->text('notes')->nullable();
            $table->string('lost_reason', 500)->nullable();
            $table->uuid('won_client_id')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->smallInteger('pipeline_position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('won_client_id')->references('id')->on('clients')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
