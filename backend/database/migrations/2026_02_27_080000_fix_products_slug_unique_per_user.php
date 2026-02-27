<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $slugUniqueExists = DB::selectOne(
                "SELECT COUNT(1) AS cnt FROM information_schema.statistics
                 WHERE table_schema = DATABASE()
                 AND table_name = 'products'
                 AND index_name = 'products_slug_unique'"
            );
            if ((int) ($slugUniqueExists->cnt ?? 0) > 0) {
                DB::statement('ALTER TABLE products DROP INDEX products_slug_unique');
            }

            $userSlugUniqueExists = DB::selectOne(
                "SELECT COUNT(1) AS cnt FROM information_schema.statistics
                 WHERE table_schema = DATABASE()
                 AND table_name = 'products'
                 AND index_name = 'products_user_id_slug_unique'"
            );
            if ((int) ($userSlugUniqueExists->cnt ?? 0) === 0) {
                DB::statement('ALTER TABLE products ADD UNIQUE products_user_id_slug_unique (user_id, slug)');
            }

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
        if (! Schema::hasTable('products')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $userSlugUniqueExists = DB::selectOne(
                "SELECT COUNT(1) AS cnt FROM information_schema.statistics
                 WHERE table_schema = DATABASE()
                 AND table_name = 'products'
                 AND index_name = 'products_user_id_slug_unique'"
            );
            if ((int) ($userSlugUniqueExists->cnt ?? 0) > 0) {
                DB::statement('ALTER TABLE products DROP INDEX products_user_id_slug_unique');
            }

            $slugUniqueExists = DB::selectOne(
                "SELECT COUNT(1) AS cnt FROM information_schema.statistics
                 WHERE table_schema = DATABASE()
                 AND table_name = 'products'
                 AND index_name = 'products_slug_unique'"
            );
            if ((int) ($slugUniqueExists->cnt ?? 0) === 0) {
                DB::statement('ALTER TABLE products ADD UNIQUE products_slug_unique (slug)');
            }

            return;
        }

        DB::statement('DROP INDEX IF EXISTS products_user_id_slug_unique');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS products_slug_unique ON products (slug)');
    }
};
