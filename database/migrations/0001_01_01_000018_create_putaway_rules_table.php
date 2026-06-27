<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('putaway_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->comment('Áp dụng cho kho nào');
            $table->unsignedBigInteger('product_id')->nullable()->comment('NULL nếu rule theo category');
            $table->unsignedBigInteger('category_id')->nullable()->comment('NULL nếu rule theo product');
            $table->unsignedBigInteger('location_id')->comment('Vị trí đích gợi ý');
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });

        // CHECK: product_id và category_id không được cùng NULL hoặc cùng có giá trị
        DB::statement("
            ALTER TABLE putaway_rules
            ADD CONSTRAINT chk_putaway_rule_target
            CHECK (
                (product_id IS NOT NULL AND category_id IS NULL)
                OR
                (product_id IS NULL AND category_id IS NOT NULL)
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('putaway_rules');
    }
};