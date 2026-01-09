<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Gender column (0 = man, 1 = woman)
            $table->enum('gender', ['0', '1'])
                  ->default('0')
                  ->comment('0 = Man, 1 = Woman')
                  ->after('products_short_description');

            // Bond column (0 = metal, 1 = diamond)
            $table->enum('bond', ['0', '1'])
                  ->default('0')
                  ->comment('0 = Metal, 1 = Diamond')
                  ->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['gender', 'bond']);
        });
    }
};
