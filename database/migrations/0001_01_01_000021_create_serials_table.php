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
            $table->unsignedBigInteger('lot_id')->nullable()->comment('NULL nếu serial không thuộc lô');
            $table->string('serial_number', 100)->comment('Số serial');
            $table->tinyInteger('status')->default(1)
                  ->comment('1=InStock, 2=Expired, 3=Consumed/OutOfStock');
            $table->string('scan_code', 500)->nullable()->comment('Barcode, tự sinh');
            $table->timestamps();

            $table->foreign('lot_id')
                  ->references('id')->on('lots')
                  ->onDelete('no action');

            // Lưu ý: spec ghi "unique per product", nhưng bảng serial không có product_id trực tiếp
            // (product truy xuất gián tiếp qua lot). Tạm thời unique toàn cục theo serial_number;
            // nếu cần unique theo product, cân nhắc thêm cột product_id hoặc validate ở tầng ứng dụng.
            $table->unique('serial_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serials');
    }
};
