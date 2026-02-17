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
        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('email_consent')->default(false)->after('sms_opted_out_at');
            $table->timestamp('email_consent_date')->nullable()->after('email_consent');
            $table->boolean('sms_consent')->default(false)->after('email_consent_date');
            $table->timestamp('sms_consent_date')->nullable()->after('sms_consent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn([
                'email_consent',
                'email_consent_date',
                'sms_consent',
                'sms_consent_date',
            ]);
        });
    }
};
