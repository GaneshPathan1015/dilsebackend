<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {

            $table->dropForeign(['parent_category_id']);

            $table->dropColumn(['carat', 'parent_category_id']);
        });
    }

    public function down(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->integer('carat')->nullable()->after('product_id');

            $table->unsignedInteger('parent_category_id')->nullable()->after('diamond_quality_id');
        });
    }
};
