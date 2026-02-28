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
        Schema::table('time_entries', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('description');
            $table->boolean('is_running')->default(false)->after('started_at');
        });

        Schema::table('time_entries', function (Blueprint $table) {
            $table->index(['user_id', 'is_running'], 'time_entries_user_running_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropIndex('time_entries_user_running_idx');
            $table->dropColumn(['started_at', 'is_running']);
        });
    }
};