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
        if (! Schema::hasTable('product_sales')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $indexExists = DB::selectOne(
                "SELECT COUNT(1) AS cnt FROM information_schema.statistics
                 WHERE table_schema = DATABASE()
                 AND table_name = 'product_sales'
                 AND index_name = 'product_sales_quote_product_unique'"
            );
            if ((int) ($indexExists->cnt ?? 0) === 0) {
                DB::statement('ALTER TABLE product_sales ADD UNIQUE product_sales_quote_product_unique (quote_id, product_id)');
            }

            return;
        }

        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS product_sales_quote_product_unique
            ON product_sales (quote_id, product_id)
            WHERE quote_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('product_sales')) {
            return;
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $indexExists = DB::selectOne(
                "SELECT COUNT(1) AS cnt FROM information_schema.statistics
                 WHERE table_schema = DATABASE()
                 AND table_name = 'product_sales'
                 AND index_name = 'product_sales_quote_product_unique'"
            );
            if ((int) ($indexExists->cnt ?? 0) > 0) {
                DB::statement('ALTER TABLE product_sales DROP INDEX product_sales_quote_product_unique');
            }

            return;
        }

        DB::statement('DROP INDEX IF EXISTS product_sales_quote_product_unique');
    }
};
