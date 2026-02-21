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
        Schema::table('users', function (Blueprint $table) {
            $table->json('email_settings')->nullable()->after('invoice_numbering_pattern');
            $table->json('sms_settings')->nullable()->after('email_settings');
            $table->json('notification_preferences')->nullable()->after('sms_settings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_settings', 'sms_settings', 'notification_preferences']);
        });
    }
};
