<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->smallInteger('email_score')->default(0)->after('position');
            $table->timestamp('email_score_updated_at')->nullable()->after('email_score');
            $table->index(['client_id', 'email_score']);
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropIndex(['client_id', 'email_score']);
            $table->dropColumn(['email_score', 'email_score_updated_at']);
        });
    }
};
