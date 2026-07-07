<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('previous_location_id')->nullable()->comment('Vị trí trước');
            $table->unsignedBigInteger('current_location_id')->comment('Vị trí hiện tại');
            $table->unsignedBigInteger('lot_id')->comment('Bắt buộc, hàng luôn theo lô');
            $table->unsignedBigInteger('serial_id')->nullable()->comment('NULL nếu hàng chỉ theo lô, có giá trị nếu theo lô + sê-ri');
            $table->decimal('quantity', 18, 3)->default(0)->comment('Số lượng thực tế');
            $table->decimal('reserved_qty', 18, 3)->default(0)->comment('Đã đặt chưa xuất');
            // available_qty: computed column, thêm bằng raw SQL bên dưới (SQL Server PERSISTED)
            $table->tinyInteger('status')->default(1)->comment('1=InStock, 2=Expired, 3=OutOfStock');
            $table->timestamp('updated_at')->nullable();

            $table->foreign('warehouse_id')
                  ->references('id')->on('warehouses')
                  ->onDelete('no action');

            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('no action');

            $table->foreign('previous_location_id')
                  ->references('id')->on('locations')
                  ->onDelete('no action');

            $table->foreign('current_location_id')
                  ->references('id')->on('locations')
                  ->onDelete('no action');

            $table->foreign('lot_id')
                  ->references('id')->on('lots')
                  ->onDelete('no action');

            $table->foreign('serial_id')
                  ->references('id')->on('serials')
                  ->onDelete('no action');

            $table->index(['warehouse_id', 'product_id']);
            $table->index('current_location_id');
            $table->index('status');
        });

        // Computed column PERSISTED — SQL Server không hỗ trợ tốt qua Blueprint,
        // nên thêm bằng raw SQL sau khi bảng đã được tạo.
        DB::statement('
            ALTER TABLE stocks
            ADD available_qty AS (quantity - reserved_qty) PERSISTED
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};