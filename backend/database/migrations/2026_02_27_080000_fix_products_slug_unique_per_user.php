<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE products DROP INDEX products_slug_unique');
            DB::statement('ALTER TABLE products ADD UNIQUE products_user_id_slug_unique (user_id, slug)');

            return;
        }

        DB::statement('DROP INDEX IF EXISTS products_slug_unique');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS products_user_id_slug_unique ON products (user_id, slug)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE products DROP INDEX products_user_id_slug_unique');
            DB::statement('ALTER TABLE products ADD UNIQUE products_slug_unique (slug)');

            return;
        }

        DB::statement('DROP INDEX IF EXISTS products_user_id_slug_unique');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS products_slug_unique ON products (slug)');
    }
};
