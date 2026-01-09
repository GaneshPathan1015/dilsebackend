<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('is_build_product', ['is_build_product','wedding','gifts','sale'])
                  ->nullable()
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('is_build_product', 150)
                  ->nullable()
                  ->change();
        });
    }
};
