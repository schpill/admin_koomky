<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_recipients', function (Blueprint $table): void {
            $table->unsignedTinyInteger('bounce_count')->default(0)->after('bounced_at');
            $table->enum('bounce_type', ['hard', 'soft'])->nullable()->after('bounce_count');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_recipients', function (Blueprint $table): void {
            $table->dropColumn(['bounce_count', 'bounce_type']);
        });
    }
};
