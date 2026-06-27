<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Mã vật tư, tự sinh');
            $table->string('name', 200);
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('uom_id')->comment('Đơn vị tính cơ bản');
            $table->unsignedBigInteger('parent_id')->nullable()
                  ->comment('NULL = vật tư gốc, có giá trị = biến thể của parent_id đó');
            $table->string('specification', 500)->nullable()->comment('Thông số kỹ thuật');
            $table->integer('alert_before_expiry')->nullable()->comment('Cảnh báo trước N ngày hết hạn');
            $table->tinyInteger('stock_rotation')->default(1)
                  ->comment('1=FIFO (mặc định), 2=FEFO, 3=Designated (Chỉ định)');
            $table->string('image_path', 500)->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Active, 0=Inactive');
            $table->tinyInteger('tracking_type')->default(1)
                  ->comment('1=Lot (mặc định), 2=LotAndSerial');
            $table->timestamps();

            $table->foreign('category_id')
                  ->references('id')->on('categories')
                  ->onDelete('no action');

            $table->foreign('uom_id')
                  ->references('id')->on('uoms')
                  ->onDelete('no action');

            $table->foreign('parent_id')
                  ->references('id')->on('products')
                  ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};