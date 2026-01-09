<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('is_build_product', 50)  // string type with 50 length
                  ->default('jewelry')              // default value
                  ->comment('jewelry, is_build_product, gift, sale')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('is_build_product', ['is_build_product','wedding','gifts','sale'])
                  ->nullable()
                  ->change();
        });
    }
};
