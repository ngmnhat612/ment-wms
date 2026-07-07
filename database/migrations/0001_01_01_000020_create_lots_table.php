<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('supplier_id')->nullable()->comment('Tạm thời không dùng đến');
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->string('lot_code', 100)->unique()->comment('Mã lô, tự sinh. VD: LO9, LO10...');
            $table->unsignedInteger('lot_number')->comment('Số lô (phần số thứ tự), tự sinh. VD: 9, 10...');
            $table->date('received_date')->nullable()->comment('Ngày nhận');
            $table->date('manufacture_date')->nullable()->comment('Ngày sản xuất');
            $table->date('expiry_date')->nullable()->comment('Ngày hết hạn');
            $table->tinyInteger('status')->default(1)
                  ->comment('1=InStock, 2=Expired, 3=Consumed/OutOfStock');
            $table->string('scan_code', 500)->nullable()->comment('QR code, tự sinh');
            $table->timestamps();

            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('no action');

            $table->foreign('supplier_id')
                  ->references('id')->on('suppliers')
                  ->onDelete('no action');

            $table->foreign('brand_id')
                  ->references('id')->on('brands')
                  ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};