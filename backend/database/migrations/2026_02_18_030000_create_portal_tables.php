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
        Schema::create('portal_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique()->index();
            $table->boolean('portal_enabled')->default(false);
            $table->string('custom_logo', 500)->nullable();
            $table->string('custom_color', 7)->nullable();
            $table->text('welcome_message')->nullable();
            $table->boolean('payment_enabled')->default(false);
            $table->boolean('quote_acceptance_enabled')->default(true);
            $table->text('stripe_publishable_key')->nullable();
            $table->text('stripe_secret_key')->nullable();
            $table->text('stripe_webhook_secret')->nullable();
            $table->json('payment_methods_enabled')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('portal_access_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('client_id')->index();
            $table->string('token', 64)->unique();
            $table->string('email', 255);
            $table->timestamp('expires_at');
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->uuid('created_by_user_id')->nullable()->index();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('portal_activity_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('client_id')->index();
            $table->uuid('portal_access_token_id')->nullable()->index();
            $table->enum('action', [
                'login',
                'logout',
                'view_dashboard',
                'view_invoice',
                'view_quote',
                'download_pdf',
                'accept_quote',
                'reject_quote',
                'make_payment',
            ])->index();
            $table->string('entity_type')->nullable();
            $table->uuid('entity_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('portal_access_token_id')->references('id')->on('portal_access_tokens')->nullOnDelete();
            $table->index(['client_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_activity_logs');
        Schema::dropIfExists('portal_access_tokens');
        Schema::dropIfExists('portal_settings');
    }
};
