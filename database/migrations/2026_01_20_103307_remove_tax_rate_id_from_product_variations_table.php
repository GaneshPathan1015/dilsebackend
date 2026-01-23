<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('product_variations', function (Blueprint $table) {

            // अगर foreign key मौजूद हो तो पहले drop करो
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'product_variations'
                AND COLUMN_NAME = 'tax_rate_id'
                AND CONSTRAINT_SCHEMA = DATABASE()
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");

            foreach ($foreignKeys as $fk) {
                $table->dropForeign($fk->CONSTRAINT_NAME);
            }

            // Column drop करो
            if (Schema::hasColumn('product_variations', 'tax_rate_id')) {
                $table->dropColumn('tax_rate_id');
            }
        });
    }

    public function down()
    {
        Schema::table('product_variations', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variations', 'tax_rate_id')) {
                $table->unsignedBigInteger('tax_rate_id')->nullable()->after('weight');
                $table->foreign('tax_rate_id')
                      ->references('id')
                      ->on('tax_rates')
                      ->onDelete('set null');
            }
        });
    }
};
