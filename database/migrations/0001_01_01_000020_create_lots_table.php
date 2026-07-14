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

            // lot_code / lot_number được sinh và duy nhất TRONG PHẠM VI 1 sản
            // phẩm (mỗi sản phẩm có dãy số Lô riêng: SP A có LO1, LO2...; SP B
            // cũng có LO1, LO2... độc lập). Vì vậy KHÔNG dùng unique() toàn cục
            // trên lot_code — dùng unique composite (product_id, lot_code) bên
            // dưới để cho phép 2 sản phẩm khác nhau cùng có cùng lot_code.
            $table->string('lot_code', 100)->comment('Mã lô, tự sinh trong phạm vi 1 sản phẩm. VD: LO9, LO10...');
            $table->unsignedInteger('lot_number')->comment('Số lô (phần số thứ tự), tự sinh trong phạm vi 1 sản phẩm. VD: 9, 10...');
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

            // Unique composite: lot_code chỉ cần duy nhất TRONG PHẠM VI 1 sản
            // phẩm, không phải toàn hệ thống.
            $table->unique(['product_id', 'lot_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};