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
            $table->string('accounting_journal_sales', 10)->default('VTE')->after('base_currency');
            $table->string('accounting_journal_purchases', 10)->default('ACH')->after('accounting_journal_sales');
            $table->string('accounting_journal_bank', 10)->default('BQ')->after('accounting_journal_purchases');
            $table->string('accounting_auxiliary_prefix', 10)->nullable()->after('accounting_journal_bank');
            $table->unsignedTinyInteger('fiscal_year_start_month')->default(1)->after('accounting_auxiliary_prefix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'accounting_journal_sales',
                'accounting_journal_purchases',
                'accounting_journal_bank',
                'accounting_auxiliary_prefix',
                'fiscal_year_start_month',
            ]);
        });
    }
};
