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
        Schema::table('invoices', function (Blueprint $table): void {
            $table->uuid('recurring_invoice_profile_id')->nullable()->after('project_id')->index();
            $table->foreign('recurring_invoice_profile_id')
                ->references('id')
                ->on('recurring_invoice_profiles')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropForeign(['recurring_invoice_profile_id']);
            $table->dropColumn('recurring_invoice_profile_id');
        });
    }
};
