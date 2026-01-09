<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('is_build_product', 50)   // string type
                  ->default('0')                     // default = jewelry (0)
                  ->comment('0=jewelry, 1=is_build_product, 2=wedding, 3=gifts, 4=sale')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('is_build_product', 50)
                  ->nullable()
                  ->change();
        });
    }
};
