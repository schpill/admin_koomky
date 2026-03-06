<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_recipients', function (Blueprint $table): void {
            $table->unique(['campaign_id', 'email'], 'campaign_recipients_campaign_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_recipients', function (Blueprint $table): void {
            $table->dropUnique('campaign_recipients_campaign_email_unique');
        });
    }
};
