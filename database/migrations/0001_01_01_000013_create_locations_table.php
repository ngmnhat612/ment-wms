<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable()->comment('Vị trí cha, phân cấp');
            $table->unsignedBigInteger('warehouse_id')->nullable()
                  ->comment('Thuộc kho nào. NULL chỉ dùng cho vị trí ảo hệ thống cấp cao (không thuộc kho cụ thể)');
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->tinyInteger('type')->default(1)
                  ->comment('1=Internal (vị trí thực trong kho), 2=Virtual (vị trí ảo)');
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
            $table->timestamps();

            $table->foreign('parent_id')
                  ->references('id')->on('locations')
                  ->onDelete('no action');

            $table->foreign('warehouse_id')
                  ->references('id')->on('warehouses')
                  ->onDelete('no action');
        });

        // Không seed VIRTUAL cứng ở đây.
        // Mỗi kho sẽ tự động có vị trí ảo riêng khi được tạo qua WarehouseService::create().
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};