<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_variants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('campaign_id')->index();
            $table->string('label', 2);
            $table->string('subject')->nullable();
            $table->text('content')->nullable();
            $table->unsignedTinyInteger('send_percent')->default(50);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('open_count')->default(0);
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('campaigns')->cascadeOnDelete();
            $table->unique(['campaign_id', 'label'], 'campaign_variants_campaign_label_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_variants');
    }
};
