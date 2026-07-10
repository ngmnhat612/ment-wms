<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->decimal('actual_qty', 18, 3)->default(0)->comment('Tồn thực tế đếm được');
            $table->decimal('diff_qty', 18, 3)
                  ->storedAs('[actual_qty] - [system_qty]')
                  ->comment('Chênh lệch');
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
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_check_detail');
    }
};
