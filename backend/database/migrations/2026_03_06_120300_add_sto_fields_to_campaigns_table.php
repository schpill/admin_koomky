<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->boolean('use_sto')->default(false)->after('settings');
            $table->unsignedTinyInteger('sto_window_hours')->default(24)->after('use_sto');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropColumn(['use_sto', 'sto_window_hours']);
        });
    }
};
