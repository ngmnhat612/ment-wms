<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_out_request_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_out_request_id');
            $table->date('issue_date')->nullable()->comment('Ngày xuất vật tư');
            $table->string('product_code', 50)->nullable();
            $table->string('product_name', 200)->nullable();
            $table->decimal('quantity', 18, 3)->default(0);
            $table->string('uom_name', 200)->nullable();
            $table->decimal('actual_qty', 18, 3)->nullable()->comment('Số lượng thực xuất');
            $table->unsignedBigInteger('receiver_id')->nullable()->comment('Người nhận (employees.id)');
            $table->string('sn_code', 50)->nullable()->comment('Mã dự án');
            $table->unsignedInteger('lot_number')->nullable()->comment('Số lô (chỉ số, khớp lots.lot_number)');
            $table->string('note', 500)->nullable();
            $table->string('serial_number', 500)->nullable()->comment('Danh sách serial, cách nhau bằng space');
            $table->timestamps();

            $table->foreign('stock_out_request_id')
                  ->references('id')->on('stock_out_request')
                  ->onDelete('cascade');

            $table->foreign('receiver_id')
                  ->references('id')->on('employees')
                  ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_out_request_detail');
    }
};
