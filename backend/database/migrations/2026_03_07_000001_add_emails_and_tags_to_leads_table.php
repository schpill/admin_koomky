<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('email_2', 255)->nullable()->after('email');
            $table->string('email_3', 255)->nullable()->after('email_2');
            $table->string('email_4', 255)->nullable()->after('email_3');
            $table->string('email_5', 255)->nullable()->after('email_4');
            $table->string('email_6', 255)->nullable()->after('email_5');
            $table->string('email_7', 255)->nullable()->after('email_6');
            $table->string('email_8', 255)->nullable()->after('email_7');
            $table->string('email_9', 255)->nullable()->after('email_8');
            $table->string('email_10', 255)->nullable()->after('email_9');
            $table->json('tags')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn([
                'email_2', 'email_3', 'email_4', 'email_5', 'email_6',
                'email_7', 'email_8', 'email_9', 'email_10', 'tags',
            ]);
        });
    }
};
