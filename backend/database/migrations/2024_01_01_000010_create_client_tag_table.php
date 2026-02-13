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
        Schema::create('client_tag', function (Blueprint $table) {
            $table->uuid('client_id');
            $table->uuid('tag_id');
            $table->timestamps();

            $table->primary(['client_id', 'tag_id']);
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_tag');
    }
};
