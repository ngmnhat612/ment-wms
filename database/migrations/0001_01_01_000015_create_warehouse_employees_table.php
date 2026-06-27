<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('employee_id');
            $table->boolean('is_primary')->default(false)
                  ->comment('Kho chính của nhân viên — dùng làm mặc định khi login');
            $table->timestamps();

            $table->foreign('warehouse_id')
                  ->references('id')->on('warehouses')
                  ->onDelete('cascade');

            $table->foreign('employee_id')
                  ->references('id')->on('employees')
                  ->onDelete('cascade');

            // Mỗi nhân viên chỉ được gán 1 lần vào mỗi kho
            $table->unique(['warehouse_id', 'employee_id'], 'warehouse_employees_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_employees');
    }
};
