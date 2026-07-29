<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('Vật tư sở hữu Sê-ri, dùng để unique theo (product_id, serial_number)');
            $table->unsignedBigInteger('lot_id')->comment('Sê-ri bắt buộc thuộc 1 Lô');
            $table->string('serial_number', 100)->comment('Số serial');
            $table->tinyInteger('status')->default(1)
                ->comment('1=InStock, 2=Expired, 3=Consumed/OutOfStock');
            $table->string('scan_code', 500)->nullable()->comment('Barcode, tự sinh');
            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')->on('products')
                ->onDelete('no action');

            $table->foreign('lot_id')
                ->references('id')->on('lots')
                ->onDelete('no action');

            // Sê-ri unique theo TỪNG Vật tư — 2 sản phẩm khác nhau được phép
            // trùng số Sê-ri, nhưng cùng 1 sản phẩm thì không.
            $table->unique(['product_id', 'serial_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serials');
    }
};
