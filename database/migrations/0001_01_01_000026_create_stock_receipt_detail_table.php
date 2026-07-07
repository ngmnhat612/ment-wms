<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_receipt_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_receipt_line_id');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable()->comment('NULL nếu hàng tracking lot');
            $table->unsignedBigInteger('location_id')->nullable()->comment('Vị trí lưu trữ');
            $table->decimal('actual_qty', 18, 3)->default(0)->comment('Số lượng thực nhập');
            $table->date('expiry_date')->nullable();
            $table->string('sub_warehouse', 50)->nullable()->comment('Kho phụ (tự nhập)');
            $table->unsignedBigInteger('receiver_id')->nullable()->comment('Người nhận (employees.id)');
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->foreign('stock_receipt_line_id')
                  ->references('id')->on('stock_receipt_line')
                  ->onDelete('cascade');

            $table->foreign('lot_id')
                  ->references('id')->on('lots')
                  ->onDelete('no action');

            $table->foreign('serial_id')
                  ->references('id')->on('serials')
                  ->onDelete('no action');

            $table->foreign('location_id')
                  ->references('id')->on('locations')
                  ->onDelete('no action');

            $table->foreign('receiver_id')
                  ->references('id')->on('employees')
                  ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_receipt_detail');
    }
};
