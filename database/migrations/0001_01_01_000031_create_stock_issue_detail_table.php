<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_issue_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_issue_line_id');
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedInteger('lot_number_snapshot')->nullable()->after('lot_id')
                  ->comment('Snapshot số Lô (lot_number) tại thời điểm nhập — dùng để hiển thị lịch sử khi lot_id đã bị xóa (phiếu Cancelled được dọn dẹp), không phụ thuộc bản ghi lots gốc còn tồn tại hay không');
            $table->unsignedBigInteger('serial_id')->nullable()->comment('NULL nếu hàng tracking lot');
            $table->string('serial_number_snapshot', 100)->nullable()
                  ->comment('Snapshot mã Sê-ri tại thời điểm xuất — dùng để hiển thị lịch sử khi serial_id đã bị xóa, không phụ thuộc bản ghi serials gốc còn tồn tại hay không');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->decimal('actual_qty', 18, 3)->default(0)->comment('Số lượng thực xuất');
            $table->string('sub_warehouse', 50)->nullable()->comment('Kho phụ');
            $table->unsignedBigInteger('receiver_id')->nullable()->comment('Người nhận (employees.id)');
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->foreign('stock_issue_line_id')
                  ->references('id')->on('stock_issue_line')
                  ->onDelete('cascade');

            $table->foreign('lot_id')
                  ->references('id')->on('lots')
                  ->onDelete('set null');

            $table->foreign('serial_id')
                  ->references('id')->on('serials')
                  ->onDelete('set null');

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
        Schema::dropIfExists('stock_issue_detail');
    }
};