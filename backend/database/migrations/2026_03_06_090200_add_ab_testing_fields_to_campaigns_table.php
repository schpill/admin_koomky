<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->boolean('is_ab_test')->default(false)->after('status');
            $table->uuid('ab_winner_variant_id')->nullable()->after('completed_at');
            $table->timestamp('ab_winner_selected_at')->nullable()->after('ab_winner_variant_id');
            $table->string('ab_winner_criteria', 20)->nullable()->after('ab_winner_selected_at');
            $table->unsignedTinyInteger('ab_auto_select_after_hours')->nullable()->after('ab_winner_criteria');

            $table->foreign('ab_winner_variant_id')
                ->references('id')
                ->on('campaign_variants')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropForeign(['ab_winner_variant_id']);
            $table->dropColumn([
                'is_ab_test',
                'ab_winner_variant_id',
                'ab_winner_selected_at',
                'ab_winner_criteria',
                'ab_auto_select_after_hours',
            ]);
        });
    }
};
