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
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedInteger('payment_terms_days')->default(30);
            $table->text('bank_details')->nullable();
            $table->text('invoice_footer')->nullable();
            $table->string('invoice_numbering_pattern', 50)->default('FAC-YYYY-NNNN');
        });

        Schema::table('time_entries', function (Blueprint $table): void {
            $table->boolean('is_billed')->default(false)->index();
            $table->timestamp('billed_at')->nullable();
        });

        Schema::create('reference_counters', function (Blueprint $table): void {
            $table->string('counter_key')->primary();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('client_id')->index();
            $table->uuid('project_id')->nullable()->index();
            $table->string('number', 20)->unique();
            $table->enum('status', [
                'draft',
                'sent',
                'viewed',
                'paid',
                'partially_paid',
                'overdue',
                'cancelled',
            ])->default('draft')->index();
            $table->date('issue_date')->index();
            $table->date('due_date')->index();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable();
            $table->decimal('discount_value', 12, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->text('notes')->nullable();
            $table->text('payment_terms')->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
        });

        Schema::create('line_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuidMorphs('documentable');
            $table->string('description');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('vat_rate', 5, 2)->default(20);
            $table->decimal('total', 12, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id')->index();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('payment_method', 50)->nullable();
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('line_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('reference_counters');

        Schema::table('time_entries', function (Blueprint $table): void {
            $table->dropColumn(['is_billed', 'billed_at']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'payment_terms_days',
                'bank_details',
                'invoice_footer',
                'invoice_numbering_pattern',
            ]);
        });
    }
};
