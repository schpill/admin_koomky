<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->index(['user_id', 'status'], 'clients_user_status_idx');
            $table->index(['user_id', 'deleted_at'], 'clients_user_deleted_idx');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->index(['user_id', 'status', 'deadline'], 'projects_user_status_deadline_idx');
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->index(['project_id', 'status', 'due_date'], 'tasks_project_status_due_idx');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->index(['user_id', 'status', 'issue_date'], 'invoices_user_status_issue_idx');
        });

        Schema::table('quotes', function (Blueprint $table): void {
            $table->index(['user_id', 'status', 'valid_until'], 'quotes_user_status_valid_until_idx');
        });

        Schema::table('credit_notes', function (Blueprint $table): void {
            $table->index(['user_id', 'status', 'issue_date'], 'credit_notes_user_status_issue_idx');
        });

        Schema::table('campaigns', function (Blueprint $table): void {
            $table->index(['user_id', 'status', 'scheduled_at'], 'campaigns_user_status_scheduled_idx');
        });

        Schema::table('campaign_recipients', function (Blueprint $table): void {
            $table->index(['campaign_id', 'status'], 'campaign_recipients_campaign_status_idx');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE INDEX IF NOT EXISTS segments_filters_gin_idx ON segments USING GIN ((filters::jsonb) jsonb_path_ops)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS segments_filters_gin_idx');
        }

        Schema::table('campaign_recipients', function (Blueprint $table): void {
            $table->dropIndex('campaign_recipients_campaign_status_idx');
        });

        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropIndex('campaigns_user_status_scheduled_idx');
        });

        Schema::table('credit_notes', function (Blueprint $table): void {
            $table->dropIndex('credit_notes_user_status_issue_idx');
        });

        Schema::table('quotes', function (Blueprint $table): void {
            $table->dropIndex('quotes_user_status_valid_until_idx');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex('invoices_user_status_issue_idx');
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex('tasks_project_status_due_idx');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropIndex('projects_user_status_deadline_idx');
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->dropIndex('clients_user_status_idx');
            $table->dropIndex('clients_user_deleted_idx');
        });
    }
};
