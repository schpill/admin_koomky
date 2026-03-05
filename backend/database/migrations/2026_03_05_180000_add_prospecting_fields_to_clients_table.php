<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('industry')->nullable()->after('country');
            $table->string('department', 10)->nullable()->after('industry');

            $table->index(['user_id', 'industry'], 'clients_user_industry_idx');
            $table->index(['user_id', 'department'], 'clients_user_department_idx');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_user_industry_idx');
            $table->dropIndex('clients_user_department_idx');
            $table->dropColumn(['industry', 'department']);
        });
    }
};
