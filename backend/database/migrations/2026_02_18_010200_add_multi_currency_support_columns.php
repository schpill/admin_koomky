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
        Schema::table('projects', function (Blueprint $table): void {
            $table->string('currency', 3)->default('EUR')->after('billing_type');
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->string('preferred_currency', 3)->nullable()->after('country')->index();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('base_currency', 3)->default('EUR')->after('invoice_numbering_pattern');
            $table->string('exchange_rate_provider', 50)->default('open_exchange_rates')->after('base_currency');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('base_currency', 3)->default('EUR')->after('currency');
            $table->decimal('exchange_rate', 12, 6)->nullable()->after('base_currency');
            $table->decimal('base_currency_total', 12, 2)->nullable()->after('exchange_rate');
        });

        Schema::table('quotes', function (Blueprint $table): void {
            $table->string('base_currency', 3)->default('EUR')->after('currency');
            $table->decimal('exchange_rate', 12, 6)->nullable()->after('base_currency');
            $table->decimal('base_currency_total', 12, 2)->nullable()->after('exchange_rate');
        });

        Schema::table('credit_notes', function (Blueprint $table): void {
            $table->string('base_currency', 3)->default('EUR')->after('currency');
            $table->decimal('exchange_rate', 12, 6)->nullable()->after('base_currency');
            $table->decimal('base_currency_total', 12, 2)->nullable()->after('exchange_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_notes', function (Blueprint $table): void {
            $table->dropColumn(['base_currency', 'exchange_rate', 'base_currency_total']);
        });

        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropColumn(['base_currency', 'exchange_rate', 'base_currency_total']);
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn(['base_currency', 'exchange_rate', 'base_currency_total']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['base_currency', 'exchange_rate_provider']);
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->dropColumn(['preferred_currency']);
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn(['currency']);
        });
    }
};
