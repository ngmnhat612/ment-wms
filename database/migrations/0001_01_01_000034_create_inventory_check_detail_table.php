<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_check_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_check_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->unsignedBigInteger('system_location_id')->nullable()->comment('Vị trí hệ thống');
            $table->unsignedBigInteger('actual_location_id')->nullable()->comment('Vị trí thực tế');
            $table->unsignedBigInteger('uom_id');
            $table->decimal('system_qty', 18, 3)->default(0)->comment('Tồn hệ thống tại thời điểm bắt đầu kiểm kê');
            $table->decimal('actual_qty', 18, 3)->nullable()->comment('Tồn thực tế đếm được (NULL = chưa kiểm)');
            // diff_qty: computed column, thêm bằng raw SQL bên dưới (SQL Server PERSISTED)
            $table->unsignedBigInteger('assignment_id')->nullable()->comment('Nhân viên được phân công (employees.id)');
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->foreign('inventory_check_id')
                  ->references('id')->on('inventory_check')
                  ->onDelete('cascade');

            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('no action');

            $table->foreign('lot_id')
                  ->references('id')->on('lots')
                  ->onDelete('no action');

            $table->foreign('serial_id')
                  ->references('id')->on('serials')
                  ->onDelete('no action');

            $table->foreign('system_location_id')
                  ->references('id')->on('locations')
                  ->onDelete('no action');

            $table->foreign('actual_location_id')
                  ->references('id')->on('locations')
                  ->onDelete('no action');

            $table->foreign('uom_id')
                  ->references('id')->on('uoms')
                  ->onDelete('no action');

            $table->foreign('assignment_id')
                  ->references('id')->on('employees')
                  ->onDelete('no action');
        });

        // Computed column PERSISTED — SQL Server không hỗ trợ tốt qua Blueprint,
        // nên thêm bằng raw SQL sau khi bảng đã được tạo.
        // NULL khi actual_qty NULL (chưa kiểm), tự tính lại mỗi khi actual_qty thay đổi.
        DB::statement('
            ALTER TABLE inventory_check_detail
            ADD diff_qty AS (actual_qty - system_qty) PERSISTED
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_check_detail');
    }
};