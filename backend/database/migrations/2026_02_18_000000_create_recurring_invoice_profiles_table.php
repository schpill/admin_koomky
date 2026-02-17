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
        Schema::create('recurring_invoice_profiles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('client_id')->index();
            $table->string('name');
            $table->enum('frequency', [
                'weekly',
                'biweekly',
                'monthly',
                'quarterly',
                'semiannual',
                'annual',
            ]);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_due_date')->index();
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->json('line_items');
            $table->text('notes')->nullable();
            $table->unsignedInteger('payment_terms_days')->default(30);
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active')->index();
            $table->timestamp('last_generated_at')->nullable();
            $table->unsignedInteger('occurrences_generated')->default(0);
            $table->unsignedInteger('max_occurrences')->nullable();
            $table->boolean('auto_send')->default(false);
            $table->string('currency', 3)->default('EUR');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_invoice_profiles');
    }
};
