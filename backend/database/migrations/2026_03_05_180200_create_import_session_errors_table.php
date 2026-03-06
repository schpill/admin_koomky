<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_session_errors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id');
            $table->unsignedInteger('row_number');
            $table->json('raw_data');
            $table->text('error_message');
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('import_sessions')->cascadeOnDelete();
            $table->index(['session_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_session_errors');
    }
};
