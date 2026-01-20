<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('diamond_master', function (Blueprint $table) {
            $table->foreignId('tax_rate_id')
                ->after('vendor_id')
                ->nullable()
                ->constrained('tax_rates')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('diamond_master', function (Blueprint $table) {
            $table->dropForeign(['tax_rate_id']);
            $table->dropColumn('tax_rate_id');
        });
    }
};
