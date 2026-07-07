<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_in_request_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_in_request_id');
            $table->string('reference_no', 100)->nullable()->comment('Số PO / Mã CU');
            $table->date('received_date')->nullable()->comment('Ngày nhận vật tư');
            $table->string('product_code', 50)->nullable()->comment('Mã vật tư (nếu đã tồn tại)');
            $table->string('product_name', 200)->nullable();
            $table->string('product_specification', 500)->nullable()->comment('Thông số kỹ thuật');
            $table->string('brand_name', 200)->nullable();
            $table->decimal('quantity', 18, 3)->default(0);
            $table->string('uom_name', 200)->nullable();
            $table->unsignedInteger('lot_number')->nullable()->comment('Số lô (chỉ số, khớp lots.lot_number)');
            $table->string('note', 500)->nullable();
            $table->string('new_product_code', 50)->nullable()->comment('Mã vật tư mới (khi product_name NULL)');
            $table->date('qc_date')->nullable();
            $table->unsignedBigInteger('qc_employee_id')->nullable()->comment('Người QC (employees.id)');
            $table->string('qc_result', 100)->nullable();
            $table->decimal('rejected_qty', 18, 3)->nullable();
            $table->string('solution', 500)->nullable()->comment('Hướng giải quyết');
            $table->unsignedBigInteger('requester_id')->nullable()->comment('Người yêu cầu (employees.id)');
            $table->timestamps();

            $table->foreign('stock_in_request_id')
                  ->references('id')->on('stock_in_request')
                  ->onDelete('cascade');

            $table->foreign('qc_employee_id')
                  ->references('id')->on('employees')
                  ->onDelete('no action');

            $table->foreign('requester_id')
                  ->references('id')->on('employees')
                  ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_in_request_detail');
    }
};
