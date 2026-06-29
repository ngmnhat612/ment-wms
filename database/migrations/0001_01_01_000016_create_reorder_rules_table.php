<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reorder_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('employee_id')->nullable()->comment('Người phụ trách theo dõi');
            $table->decimal('min_qty', 18, 3)->default(0)->comment('Ngưỡng tồn tối thiểu — cảnh báo khi tồn < min_qty');
            $table->decimal('max_qty', 18, 3)->default(0)->comment('Ngưỡng tồn tối đa mong muốn');
            $table->string('note', 500)->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
            $table->timestamps();

            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            $table->foreign('warehouse_id')
                  ->references('id')->on('warehouses')
                  ->onDelete('cascade');

            $table->foreign('employee_id')
                  ->references('id')->on('employees')
                  ->onDelete('set null');

            // Mỗi (product + warehouse) chỉ có 1 reorder rule
            $table->unique(['product_id', 'warehouse_id'], 'reorder_rules_product_warehouse_unique');

            $table->index(['status', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reorder_rules');
    }
};
