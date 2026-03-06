<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_recipients', function (Blueprint $table): void {
            $table->uuid('variant_id')->nullable()->after('contact_id');
            $table->index(['campaign_id', 'variant_id'], 'campaign_recipients_campaign_variant_idx');

            $table->foreign('variant_id')
                ->references('id')
                ->on('campaign_variants')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('campaign_recipients', function (Blueprint $table): void {
            $table->dropForeign(['variant_id']);
            $table->dropIndex('campaign_recipients_campaign_variant_idx');
            $table->dropColumn('variant_id');
        });
    }
};
