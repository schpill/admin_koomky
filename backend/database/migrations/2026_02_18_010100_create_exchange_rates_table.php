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
        Schema::create('exchange_rates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('base_currency', 3)->index();
            $table->string('target_currency', 3)->index();
            $table->decimal('rate', 12, 6);
            $table->timestamp('fetched_at')->index();
            $table->date('rate_date')->index();
            $table->string('source', 50);
            $table->timestamps();

            $table->unique(['base_currency', 'target_currency', 'rate_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
