<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable()->comment('Kho cha, hỗ trợ phân cấp');
            $table->unsignedBigInteger('manager_id')->nullable()->comment('Nhân viên quản lý kho');
            // root_location_id sẽ được thêm FK sau khi tạo bảng locations
            // (tránh circular FK: warehouses → locations → warehouses)
            $table->unsignedBigInteger('root_location_id')->nullable()->comment('Vị trí gốc của kho');
            $table->string('code', 50)->unique()->comment('Mã kho, tự sinh');
            $table->string('name', 200);
            $table->string('phone', 20)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('note', 500)->nullable()->comment('Ghi chú');
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
            $table->timestamps();

            $table->foreign('parent_id')
                  ->references('id')->on('warehouses')
                  ->onDelete('no action');

            $table->foreign('manager_id')
                  ->references('id')->on('employees')
                  ->onDelete('set null');

            // FK root_location_id → locations sẽ được thêm bằng migration phụ sau
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};