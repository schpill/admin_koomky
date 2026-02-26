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
        Schema::table('line_items', function (Blueprint $table) {
            $table->foreignUuid('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('line_items', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }
};
