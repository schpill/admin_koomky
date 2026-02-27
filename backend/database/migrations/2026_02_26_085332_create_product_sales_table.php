<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignUuid('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignUuid('quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->char('currency_code', 3);
            $table->string('status', 50);
            $table->timestamp('sold_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['user_id', 'sold_at']);
        });

        // Partial unique index: unique (invoice_id, product_id) WHERE invoice_id IS NOT NULL
        DB::statement('
            CREATE UNIQUE INDEX product_sales_invoice_product_unique
            ON product_sales (invoice_id, product_id)
            WHERE invoice_id IS NOT NULL
        ');

        // Partial unique index: unique (quote_id, product_id) WHERE quote_id IS NOT NULL
        DB::statement('
            CREATE UNIQUE INDEX product_sales_quote_product_unique
            ON product_sales (quote_id, product_id)
            WHERE quote_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_sales');
    }
};
